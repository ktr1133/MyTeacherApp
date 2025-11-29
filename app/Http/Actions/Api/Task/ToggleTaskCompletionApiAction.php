<?php

namespace App\Http\Actions\Api\Task;

use App\Models\Task;
use App\Services\Task\TaskManagementServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: タスク完了状態トグルアクション
 * 
 * モバイルアプリからのタスク完了/未完了切り替えリクエストを処理
 * Cognito認証を前提（middleware: cognito）
 */
class ToggleTaskCompletionApiAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected TaskManagementServiceInterface $taskService
    ) {}

    /**
     * タスクの完了状態をトグル
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
                    'message' => 'このタスクを操作する権限がありません。',
                ], 403);
            }

            // 完了状態をトグル
            $task->is_completed = !$task->is_completed;
            $task->completed_at = $task->is_completed ? now() : null;
            $task->save();

            // キャッシュクリア
            $this->taskService->clearUserTaskCache($user->id);

            // レスポンス
            return response()->json([
                'success' => true,
                'message' => $task->is_completed ? 'タスクを完了にしました。' : 'タスクを未完了に戻しました。',
                'data' => [
                    'task' => [
                        'id' => $task->id,
                        'is_completed' => $task->is_completed,
                        'completed_at' => $task->completed_at?->toIso8601String(),
                        'updated_at' => $task->updated_at->toIso8601String(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('API: タスク完了トグルエラー', [
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
