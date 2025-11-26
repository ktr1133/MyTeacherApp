<?php

namespace App\Services\Batch;

use App\Models\ScheduledGroupTask;
use App\Repositories\Batch\ScheduledTaskRepositoryInterface;
use App\Repositories\Batch\HolidayRepositoryInterface;
use App\Repositories\Profile\ProfileUserRepositoryInterface;
use App\Repositories\Task\TaskRepositoryInterface;
use App\Services\Notification\NotificationServiceInterface;
use App\Services\Batch\ScheduledTaskServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ScheduledTaskService implements ScheduledTaskServiceInterface
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private ScheduledTaskRepositoryInterface $scheduledTaskRepository,
        private TaskRepositoryInterface $taskRepository,
        private HolidayRepositoryInterface $holidayRepository,
        private NotificationServiceInterface $notificationService,
    ) {}

    /**
     * 今日実行すべきスケジュールタスクを処理
     */
    public function executeScheduledTasks(?\DateTime $date = null): array
    {
        $date = $date ?? now();
        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        // 今日実行すべきスケジュールを取得
        $scheduledTasks = $this->scheduledTaskRepository->getTasksShouldRunData($date);

        foreach ($scheduledTasks as $scheduledTask) {
            $result = $this->executeScheduledTask($scheduledTask, $date);
            $results[$result]++;
        }

        return $results;
    }

    /**
     * 個別のスケジュールタスクを実行
     */
    public function executeScheduledTask(ScheduledGroupTask $scheduledTask, ?\DateTime $date = null): string
    {
        $date = $date ?? now();

        try {
            DB::beginTransaction();

            // 祝日チェック
            if ($this->shouldSkipDueToHoliday($scheduledTask, $date)) {
                $this->scheduledTaskRepository->recordExecution([
                    'scheduled_task_id' => $scheduledTask->id,
                    'created_task_id' => null,
                    'deleted_task_id' => null,
                    'executed_at' => now(),
                    'status' => 'skipped',
                    'note' => '祝日のためスキップ',
                ]);
                DB::commit();
                return 'skipped';
            }

            // スケジュールマッチチェック
            if (!$this->matchesSchedule($scheduledTask, $date)) {
                DB::commit();
                return 'skipped';
            }

            // 重複チェック
            if ($this->scheduledTaskRepository->isAlreadyExecutedToday($scheduledTask->id, $date)) {
                DB::commit();
                return 'skipped';
            }

            // 前回の未完了タスクを処理
            $deletedTaskId = $this->handlePreviousIncompleteTask($scheduledTask);

            // 新しいタスクを作成
            $newTask = $this->createTaskFromSchedule($scheduledTask, $date);

            // 実行履歴を記録
            $this->scheduledTaskRepository->recordExecution([
                'scheduled_task_id' => $scheduledTask->id,
                'created_task_id' => $newTask->id,
                'deleted_task_id' => $deletedTaskId,
                'executed_at' => now(),
                'status' => 'success',
                'note' => null,
            ]);

            // 【将来の通知機能用】
            // $this->notifyTaskCreation($newTask);

            DB::commit();

            return 'success';

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->scheduledTaskRepository->recordExecution([
                'scheduled_task_id' => $scheduledTask->id,
                'created_task_id' => null,
                'deleted_task_id' => null,
                'executed_at' => now(),
                'status' => 'failed',
                'note' => null,
                'error_message' => $e->getMessage(),
            ]);
            
            Log::error("Failed to execute scheduled task", [
                'scheduled_task_id' => $scheduledTask->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 'failed';
        }
    }

    /**
     * スケジュールタスクを作成
     */
    public function createScheduledTask(array $data): ScheduledGroupTask
    {
        return $this->scheduledTaskRepository->create($data);
    }

    /**
     * スケジュールタスクを更新
     */
    public function updateScheduledTask(int $id, array $data): bool
    {
        return $this->scheduledTaskRepository->update($id, $data);
    }

    /**
     * スケジュールタスクを削除
     */
    public function deleteScheduledTask(int $id): bool
    {
        return $this->scheduledTaskRepository->delete($id);
    }

    /**
     * スケジュールタスクを一時停止
     */
    public function pauseScheduledTask(int $id): bool
    {
        return $this->scheduledTaskRepository->pause($id);
    }

    /**
     * スケジュールタスクを再開
     */
    public function resumeScheduledTask(int $id): bool
    {
        return $this->scheduledTaskRepository->resume($id);
    }

    /**
     * 祝日のためスキップすべきかチェック
     * 
     * @param ScheduledGroupTask $scheduledTask スケジュールタスク
     * @param \DateTime $date 判定基準日時（UTC）
     * @return bool スキップする場合true
     */
    protected function shouldSkipDueToHoliday(ScheduledGroupTask $scheduledTask, \DateTime $date): bool
    {
        if (!$scheduledTask->skip_holidays && !$scheduledTask->move_to_next_business_day) {
            return false;
        }

        // 作成者のタイムゾーンで日付を取得
        $creator = $scheduledTask->creator;
        if (!$creator) {
            Log::warning('Scheduled task creator not found for holiday check', ['scheduled_task_id' => $scheduledTask->id]);
            return false;
        }
        
        // UTC時刻を作成者のタイムゾーンに変換して日付を取得
        $userTimezone = $creator->timezone ?? 'Asia/Tokyo';
        $dateInUserTz = Carbon::parse($date)->timezone($userTimezone);
        
        $isHoliday = $this->holidayRepository->isHoliday($dateInUserTz);

        if (!$isHoliday) {
            return false;
        }

        // 祝日をスキップする設定の場合
        if ($scheduledTask->skip_holidays) {
            return true;
        }

        // 翌営業日に移動する設定の場合は、今日が祝日でも実行しない
        return false;
    }

    /**
     * スケジュールとマッチするかチェック
     * 
     * @param ScheduledGroupTask $scheduledTask スケジュールタスク
     * @param \DateTime $date 判定基準日時（UTC）
     * @return bool マッチする場合true
     */
    protected function matchesSchedule(ScheduledGroupTask $scheduledTask, \DateTime $date): bool
    {
        $schedules = $scheduledTask->schedules;
        
        // 作成者のタイムゾーンで現在時刻を取得
        $creator = $scheduledTask->creator;
        if (!$creator) {
            Log::warning('Scheduled task creator not found', [
                'scheduled_task_id' => $scheduledTask->id,
                'created_by' => $scheduledTask->created_by,
            ]);
            return false;
        }
        
        // UTC時刻を作成者のタイムゾーンに変換
        $userTimezone = $creator->timezone ?? 'Asia/Tokyo';
        $dateInUserTz = Carbon::parse($date)->timezone($userTimezone);
        $currentTime = $dateInUserTz->format('H:i');
        $dayOfWeek = (int)$dateInUserTz->format('w'); // 0=日曜, 6=土曜
        $dayOfMonth = (int)$dateInUserTz->format('j'); // 1-31

        Log::debug('Checking schedule match', [
            'scheduled_task_id' => $scheduledTask->id,
            'creator_id' => $creator->id,
            'creator_timezone' => $userTimezone,
            'utc_time' => $date->format('Y-m-d H:i:s'),
            'user_local_time' => $dateInUserTz->format('Y-m-d H:i:s'),
            'current_time' => $currentTime,
        ]);

        foreach ($schedules as $schedule) {
            // 時刻が一致しない場合はスキップ
            if (isset($schedule['time']) && $schedule['time'] !== $currentTime) {
                continue;
            }

            // スケジュールタイプ別の判定
            switch ($schedule['type']) {
                case 'daily':
                    return true;

                case 'weekly':
                    return in_array($dayOfWeek, $schedule['days'] ?? []);

                case 'monthly':
                    return in_array($dayOfMonth, $schedule['dates'] ?? []);
            }
        }

        return false;
    }

    /**
     * 前回の未完了タスクを処理
     */
    protected function handlePreviousIncompleteTask(ScheduledGroupTask $scheduledTask): ?int
    {
        if (!$scheduledTask->delete_incomplete_previous) {
            return null;
        }

        // 最後に作成したタスクを取得
        $lastExecution = $this->scheduledTaskRepository->getLastSuccessfulExecution($scheduledTask->id);

        if (!$lastExecution || !$lastExecution->created_task_id) {
            return null;
        }

        $lastTask = $this->taskRepository->findTaskById($lastExecution->created_task_id);

        // タスクが存在し、未完了の場合のみ論理削除
        if ($lastTask && !$lastTask->is_completed && !$lastTask->trashed()) {
            $this->taskRepository->softDeleteById($lastTask->id);
            
            Log::info("Previous incomplete task deleted", [
                'task_id' => $lastTask->id,
                'scheduled_task_id' => $scheduledTask->id,
            ]);

            return $lastTask->id;
        }

        return null;
    }

    /**
     * スケジュールから新しいタスクを作成
     */
    protected function createTaskFromSchedule(ScheduledGroupTask $scheduledTask, \DateTime $date)
    {
        // 担当者の決定
        $assignedUserId = $this->determineAssignedUser($scheduledTask);

        // 期限の計算
        $dueDate = $this->calculateDueDate($scheduledTask, $date);

        // タスク作成データを準備
        $taskData = [
            'title'               => $scheduledTask->title,
            'span'                => config('const.task_spans.short'),
            'description'         => $scheduledTask->description,
            'group_id'            => $scheduledTask->group_id,
            'assigned_by_user_id' => $scheduledTask->created_by,
            'group_task_id'       => (string) Str::uuid(),
            'due_date'            => $dueDate,
            'requires_image'      => $scheduledTask->requires_image,
            'requires_approval'   => $scheduledTask->requires_approval,
            'reward'              => $scheduledTask->reward,
            'created_by'          => $scheduledTask->created_by,
        ];

        // 担当者が未設定の場合は編集権限のないメンバ全員向けのタスクを作成
        if (!$assignedUserId) {
            $groupMembers = $this->profileUserRepository->getGroupMembersByGroupId($scheduledTask->group_id);
            foreach ($groupMembers as $member) {
                $taskData['user_id'] = $member->id;

                // タスク作成
                $task = $this->taskRepository->create($taskData);

                // タグの紐付け
                $tagNames = $scheduledTask->getTagNames();
                if (!empty($tagNames)) {
                    $this->taskRepository->attachTagsForBatch($task->id, $tagNames);
                }
            }
            // 担当者に通知
            $this->notificationService->sendNotificationForGroup(
                config('const.notification_types.group_task_created'),
                '新しいグループタスクが作成されました。',
                '新しいグループタスク: ' . $taskData['title'] . 'が作成されました。タスクリストを確認してください。',
                'important'
            );
        // 担当者指定の場合はその担当者向けのタスクを作成
        } else {
            $taskData['user_id'] = $assignedUserId;

            // タスク作成
            $task = $this->taskRepository->create($taskData);

            // タグの紐付け
            $tagNames = $scheduledTask->getTagNames();
            if (!empty($tagNames)) {
                $this->taskRepository->attachTagsForBatch($task->id, $tagNames);
            }
            // 担当者に通知を送信
            $this->notificationService->sendNotification(
                Auth::user()->id,
                $assignedUserId,
                config('const.notification_types.group_task_created'),
                '新しいグループタスクが作成されました。',
                '新しいグループタスク: ' . $taskData['title'] . 'が作成されました。タスクリストを確認してください。',
                'important'
            );       
        }

        return $task;
    }

    /**
     * 担当者を決定
     */
    protected function determineAssignedUser(ScheduledGroupTask $scheduledTask): ?int
    {
        // 自動割り当ての場合
        if ($scheduledTask->auto_assign) {
            $groupMembers = $this->taskRepository->getGroupMemberIds($scheduledTask->group_id);
            
            if (empty($groupMembers)) {
                return null;
            }

            return $groupMembers[array_rand($groupMembers)];
        }

        // 指定された担当者
        return $scheduledTask->assigned_user_id;
    }

    /**
     * 期限を計算
     * 
     * @param ScheduledGroupTask $scheduledTask スケジュールタスク
     * @param \DateTime $date 基準日時（UTC）
     * @return \DateTime|null 期限日時（UTC）
     */
    protected function calculateDueDate(ScheduledGroupTask $scheduledTask, \DateTime $date): ?\DateTime
    {
        if (!$scheduledTask->due_duration_days && !$scheduledTask->due_duration_hours) {
            return null;
        }

        // 作成者のタイムゾーンで基準日時を取得
        $creator = $scheduledTask->creator;
        $userTimezone = $creator ? ($creator->timezone ?? 'Asia/Tokyo') : 'Asia/Tokyo';
        
        // UTC時刻を作成者のタイムゾーンに変換してから期限を計算
        $dueDate = Carbon::parse($date)->timezone($userTimezone);

        if ($scheduledTask->due_duration_days) {
            $dueDate->addDays($scheduledTask->due_duration_days);
        }

        if ($scheduledTask->due_duration_hours) {
            $dueDate->addHours($scheduledTask->due_duration_hours);
        }

        // UTCに戻して返す（データベース保存用）
        return $dueDate->timezone('UTC');
    }
}