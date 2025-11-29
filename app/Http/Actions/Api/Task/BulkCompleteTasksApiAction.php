<?php

namespace App\Http\Actions\Api\Task;

use App\Models\Task;
use App\Services\Task\TaskManagementServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * API: 複数タスク一括完了/未完了アクション
 * 
 * モバイルアプリからの複数タスク完了状態変更リクエストを処理
 * Cognito認証を前提（middleware: cognito）
 */
final class BulkCompleteTasksApiAction
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
        try {
            // 認証済みユーザーを取得
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'ユーザー認証に失敗しました。',
                ], 401);
            }

            // バリデーション
            $validated = $request->validate([
                'task_ids' => 'required|array|min:1',
                'task_ids.*' => 'required|integer|exists:tasks,id',
                'is_completed' => 'required|boolean',
            ]);

            $taskIds = $validated['task_ids'];
            $isCompleted = $validated['is_completed'];

            $updatedCount = 0;

            DB::transaction(function () use ($user, $taskIds, $isCompleted, &$updatedCount) {
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

                $updatedCount = $tasks->count();

                // キャッシュクリア
                $this->taskService->clearUserTaskCache($user->id);

                Log::info('[BulkCompleteTasksApiAction] Tasks bulk updated', [
                    'user_id' => $user->id,
                    'task_count' => $updatedCount,
                    'is_completed' => $isCompleted,
                ]);
            });

            $message = $isCompleted 
                ? "{$updatedCount}件のタスクを完了にしました。"
                : "{$updatedCount}件のタスクを未完了に戻しました。";

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'updated_count' => $updatedCount,
                    'is_completed' => $isCompleted,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[BulkCompleteTasksApiAction] Validation failed', [
                'user_id' => $request->user()?->id,
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラー',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {
            Log::error('[BulkCompleteTasksApiAction] Failed to bulk update tasks', [
                'user_id' => $request->user()?->id,
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
