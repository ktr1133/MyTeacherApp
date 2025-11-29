<?php

namespace App\Http\Actions\Api\Task;

use App\Http\Requests\Task\StoreTaskRequest;
use App\Services\Task\TaskManagementServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * API: タスク作成アクション
 * 
 * モバイルアプリからのタスク作成リクエストを処理
 * Cognito認証を前提（middleware: cognito）
 */
class StoreTaskApiAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected TaskManagementServiceInterface $taskService
    ) {}

    /**
     * タスクを作成
     *
     * @param StoreTaskRequest $request バリデーション済みリクエスト
     * @return JsonResponse
     */
    public function __invoke(StoreTaskRequest $request): JsonResponse
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

            // タスクを作成（グループタスクフラグを渡す）
            $task = $this->taskService->createTask(
                $user,
                $request->validated(),
                $request->isGroupTask()
            );

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'タスクの作成に失敗しました。',
                ], 500);
            }

            // レスポンス
            return response()->json([
                'success' => true,
                'message' => 'タスクを作成しました。',
                'data' => [
                    'task' => [
                        'id' => $task->id,
                        'title' => $task->title,
                        'description' => $task->description,
                        'span' => $task->span,
                        'due_date' => $task->due_date?->format('Y-m-d'),
                        'priority' => $task->priority,
                        'status' => $task->status,
                        'reward' => $task->reward,
                        'requires_approval' => $task->requires_approval,
                        'requires_image' => $task->requires_image,
                        'is_group_task' => $task->group_task_id !== null,
                        'group_task_id' => $task->group_task_id,
                        'assigned_by_user_id' => $task->assigned_by_user_id,
                        'created_at' => $task->created_at->toIso8601String(),
                        'updated_at' => $task->updated_at->toIso8601String(),
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('API: タスク作成エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'サーバーエラーが発生しました。',
            ], 500);
        }
    }
}
