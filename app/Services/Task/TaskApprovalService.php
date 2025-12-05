<?php

namespace App\Services\Task;

use App\Models\Task;
use App\Models\User;
use App\Models\TaskImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Task\TaskRepositoryInterface;
use App\Services\Notification\NotificationServiceInterface;

class TaskApprovalService implements TaskApprovalServiceInterface
{
    /**
     * Constructor
     */
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private NotificationServiceInterface $notificationService,
        private TaskManagementServiceInterface $taskManagementService,
    ) {}

    /**
     * タスクの完了申請を行う。
     * @param Task $task
     * @param User $user
     * @return Task
     */
    public function requestApproval(Task $task, User $user): Task
    {
        if (!$task->requires_approval) {
            abort(400, 'このタスクは承認不要です。');
        }

        if ($task->user_id !== $user->id) {
            abort(403, 'このタスクの完了申請権限がありません。');
        }

        if ($task->is_completed || $task->approved_at) {
            abort(400, 'このタスクは既に申請中または承認済みです。');
        }

        if (!$task->canComplete()) {
            abort(400, '画像の添付が必要です。');
        }

        return DB::transaction(function () use ($task, $user) {
            // 申請したユーザの完了申請を記録
            $task = $this->taskRepository->update($task, [
                'is_completed' => true,
                'completed_at' => now(),
            ]);

            // 同一グループタスクの他メンバー分を論理削除
            $groupTaskId = $task->group_task_id;
            if ($groupTaskId) {
                $this->taskRepository->deleteByGroupTaskIdExcludingUser($groupTaskId, $user->id);                
            }

            // 承認者に申請完了を通知
            $title = '完了申請';
            $userName = $user->username;
            $message = $userName . 'からタスク: ' . $task->title . ' の完了申請がありました。';
            $this->notificationService->sendNotification($task->user_id, $task->assigned_by_user_id, config('const.notification_types.approval_required'), $title, $message);

            // キャッシュをクリア（最新データを反映させるため）
            $this->taskManagementService->clearUserTaskCache($task->user_id);

            return $task;
        });
    }

    /**
     * タスクを承認する。
     * @param Task $task
     * @param User $approver
     * @return Task
     */
    public function approveTask(Task $task, User $approver): Task
    {
        // 承認権限チェック（割り当てた人 or グループマスター）
        if ($task->assigned_by_user_id !== $approver->id && !$approver->canEditGroup()) {
            abort(403, 'このタスクの承認権限がありません。');
        }

        return DB::transaction(function () use ($task, $approver) {
            // タスクを承認済みに更新
            $task = $this->taskRepository->update($task, [
                'approved_at' => now(),
                'approved_by_user_id' => $approver->id,
            ]);

            // 申請者に承認完了を通知
            $title = '承認完了';
            $approverName = $approver->username;
            $message = $approverName . 'があなたのタスク: ' . $task->title . ' を承認しました。';
            $this->notificationService->sendNotification($task->approved_by_user_id, $task->user_id, config('const.notification_types.task_approved'), $title, $message);

            return $task;
        });
    }

    /**
     * タスクを却下する。
     * @param Task $task
     * @param User $approver
     * @param string|null $reason
     * @return Task
     */
    public function rejectTask(Task $task, User $approver, ?string $reason): Task
    {
        if (!$task->requires_approval || !$task->is_completed || $task->approved_at) {
            abort(400, 'このタスクは却下できません。');
        }

        if ($task->assigned_by_user_id !== $approver->id && !$approver->canEditGroup()) {
            abort(403, 'このタスクの却下権限がありません。');
        }

        return DB::transaction(function () use ($task, $approver) {
            // 却下されたタスクを未完了に戻す
            $task = $this->taskRepository->update($task, [
                'is_completed' => false,
                'completed_at' => null,
            ]);

            // 同一グループタスクの削除済みレコードを復元
            if ($task->group_task_id) {
                $this->taskRepository->restoreByGroupTaskId((string) $task->group_task_id);
            }

            // 申請者に承認却下を通知
            $title = '承認却下';
            $approverName = $approver->username;
            $message = $approverName . 'があなたのタスク: ' . $task->title . ' を却下しました。';
            $this->notificationService->sendNotification($approver->id, $task->user_id, config('const.notification_types.task_rejected'), $title, $message);

            return $task;
        });
    }

    /**
     * 承認待ちタスクの一覧を取得する。
     * @param User $user
     * @return Collection
     */
    public function getPendingApprovals(User $user): Collection
    {
        if (!$user->canEditGroup()) {
            return collect();
        }

        // グループメンバーの承認待ちタスクを取得
        return Task::query()
            ->where('requires_approval', true)
            ->where('is_completed', true)
            ->whereNull('approved_at')
            ->whereHas('user', function ($q) use ($user) {
                $q->where('group_id', $user->group_id);
            })
            ->with(['user', 'images', 'tags'])
            ->orderBy('completed_at', 'desc')
            ->get();
    }

    /**
     * 承認者として承認待ちタスク一覧を取得（統合画面用）
     * 
     * @param User $approver 承認者
     * @return Collection
     */
    public function getPendingTasksForApprover(User $approver): Collection
    {
        if (!$approver->canEditGroup()) {
            return collect();
        }

        // グループメンバーの承認待ちタスクを取得
        return Task::query()
            ->where('requires_approval', true)
            ->where('is_completed', true)
            ->whereNull('approved_at')
            ->whereHas('user', function ($q) use ($approver) {
                $q->where('group_id', $approver->group_id);
            })
            ->with(['user', 'images', 'tags'])
            ->orderBy('completed_at', 'asc') // 古い順
            ->get();
    }

    /**
     * タスクに画像をアップロードする。
     * @param Task $task
     * @param UploadedFile $file
     * @return TaskImage
     */
    public function uploadImage(Task $task, UploadedFile $file): TaskImage
    {
        $path = $file->store('task-images', 's3');

        return TaskImage::create([
            'task_id' => $task->id,
            'file_path' => $path,
        ]);
    }

    /**
     * タスク画像を削除する。
     * @param TaskImage $image
     * @return bool
     */
    public function deleteImage(TaskImage $image): bool
    {
        // S3ディスクから画像を削除
        if ($image->file_path && Storage::disk('s3')->exists($image->file_path)) {
            Storage::disk('s3')->delete($image->file_path);
        }
        return $image->delete();
    }

    /**
     * タスクを承認不要で完了する。
     * @param Task $task
     * @param User $user
     * @return Task
     */
    public function completeWithoutApproval(Task $task, User $user): Task
    {
        if ($task->user_id !== $user->id) {
            abort(403, 'このタスクの完了申請権限がありません。');
        }

        if (!$task->canComplete()) {
            abort(400, '画像の添付が必要です。');
        }

        return DB::transaction(function () use ($task, $user) {
            // 申請したユーザの完了申請を記録
            $task = $this->taskRepository->update($task, [
                'is_completed' => true,
                'completed_at' => now(),
            ]);

            // 同一グループタスクの他メンバー分を論理削除
            $groupTaskId = $task->group_task_id;
            if ($groupTaskId) {
                $this->taskRepository->deleteByGroupTaskIdExcludingUser($groupTaskId, $user->id);                
            }

            // キャッシュをクリア（最新データを反映させるため）
            $this->taskManagementService->clearUserTaskCache($task->user_id);

            return $task;
        });
    }

    /**
     * タスクを通知なしで承認する。
     * @param Task $task
     * @param User $approver
     * @return Task
     */
    public function approveTaskWithoutNotification(Task $task, User $approver): Task
    {
        // 承認権限チェック（割り当てた人 or グループマスター）
        if ($task->assigned_by_user_id !== $approver->id && !$approver->canEditGroup()) {
            abort(403, 'このタスクの承認権限がありません。');
        }

        return DB::transaction(function () use ($task, $approver) {
            // タスクを承認済みに更新
            $task = $this->taskRepository->update($task, [
                'approved_at' => now(),
                'approved_by_user_id' => $approver->id,
            ]);

            return $task;
        });
    }
}