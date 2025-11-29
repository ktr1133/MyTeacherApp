<?php

namespace App\Http\Actions\Api\Task;

use App\Models\Task;
use App\Services\Task\TaskManagementServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: タスク削除アクション
 * 
 * モバイルアプリからのタスク削除リクエストを処理
 * Cognito認証を前提（middleware: cognito）
 */
class DestroyTaskApiAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected TaskManagementServiceInterface $taskService
    ) {}

    /**
     * タスクを削除
     *
     * @param Request $request
     * @param Task $task ルートモデルバインディング
     * @return JsonResponse
     */
    public function __invoke(Request $request, Task $task): JsonResponse
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
                    'message' => 'このタスクを削除する権限がありません。',
                ], 403);
            }

            // タスク削除
            $deleted = $this->taskService->deleteTask($task);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'タスクの削除に失敗しました。',
                ], 500);
            }

            // レスポンス
            return response()->json([
                'success' => true,
                'message' => 'タスクを削除しました。',
            ], 200);

        } catch (\Exception $e) {
            Log::error('API: タスク削除エラー', [
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
