<?php

namespace App\Http\Actions\Api\Task;

use App\Models\Task;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Services\Task\TaskManagementServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * API: タスク更新アクション
 * 
 * モバイルアプリからのタスク更新リクエストを処理
 * Cognito認証を前提（middleware: cognito）
 */
class UpdateTaskApiAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected TaskManagementServiceInterface $taskService
    ) {}

    /**
     * タスクを更新
     *
     * @param StoreTaskRequest $request バリデーション済みリクエスト
     * @param Task $task ルートモデルバインディング
     * @return JsonResponse
     */
    public function __invoke(StoreTaskRequest $request, Task $task): JsonResponse
    {
        try {
            // 認証済みユーザーを取得（VerifyCognitoTokenで注入済み）
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'ユーザー認証に失敗しました。',
                ], 401);
            }

            // 所有権チェック
            if ($task->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'このタスクを更新する権限がありません。',
                ], 403);
            }

            // タスクを更新
            $updatedTask = $this->taskService->updateTask($task, $request->validated());

            // レスポンス
            return response()->json([
                'success' => true,
                'message' => 'タスクを更新しました。',
                'data' => [
                    'task' => [
                        'id' => $updatedTask->id,
                        'title' => $updatedTask->title,
                        'description' => $updatedTask->description,
                        'span' => $updatedTask->span,
                        'due_date' => $updatedTask->due_date?->format('Y-m-d'),
                        'priority' => $updatedTask->priority,
                        'status' => $updatedTask->status,
                        'updated_at' => $updatedTask->updated_at->toIso8601String(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('API: タスク更新エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'task_id' => $task->id,
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'サーバーエラーが発生しました。',
            ], 500);
        }
    }
}
