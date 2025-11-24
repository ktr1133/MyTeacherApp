<?php

namespace App\Http\Actions\Task;

use App\Models\Task;
use App\Services\Task\TaskManagementServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 複数タスクを一括で完了/未完了にするアクション
 */
final class BulkCompleteTasksAction
{
    /**
     * コンストラクタ
     *
     * @param TaskManagementServiceInterface $taskService タスク管理サービス
     */
    public function __construct(
        private TaskManagementServiceInterface $taskService
    ) {}

    /**
     * 複数タスクの完了状態を一括変更
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'task_ids' => 'required|array|min:1',
            'task_ids.*' => 'required|integer|exists:tasks,id',
            'is_completed' => 'required|boolean',
        ]);

        $user = $request->user();
        $taskIds = $validated['task_ids'];
        $isCompleted = $validated['is_completed'];

        try {
            DB::transaction(function () use ($user, $taskIds, $isCompleted) {
                // ユーザーの所有タスクのみ取得（権限チェック）
                $tasks = Task::whereIn('id', $taskIds)
                    ->where('user_id', $user->id)
                    ->get();

                if ($tasks->isEmpty()) {
                    throw new \RuntimeException('対象のタスクが見つかりません。');
                }

                // 一括更新
                foreach ($tasks as $task) {
                    $task->is_completed = $isCompleted;
                    $task->completed_at = $isCompleted ? now() : null;
                    $task->save();
                }

                // キャッシュクリア
                $this->taskService->clearUserTaskCache($user->id);

                Log::info('[BulkCompleteTasksAction] Tasks bulk updated', [
                    'user_id' => $user->id,
                    'task_count' => $tasks->count(),
                    'is_completed' => $isCompleted,
                ]);
            });

            $count = count($taskIds);

            // アバターイベント設定
            if ($isCompleted) {
                $avatarEvent = config('const.avatar_events.task_completed');
                $message = "{$count}件のタスクを完了にしました。";
            } else {
                $avatarEvent = config('const.avatar_events.task_deleted');
                $message = "{$count}件のタスクを未完了に戻しました。";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'avatar_event' => $avatarEvent,
                'count' => $count,
            ]);

        } catch (\Throwable $e) {
            Log::error('[BulkCompleteTasksAction] Failed to bulk update tasks', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'タスクの一括更新に失敗しました。',
            ], 500);
        }
    }
}
