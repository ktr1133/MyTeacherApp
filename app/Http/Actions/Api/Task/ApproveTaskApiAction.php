<?php

namespace App\Http\Actions\Api\Task;

use App\Models\Task;
use App\Services\Task\TaskApprovalServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: タスク承認アクション
 * 
 * モバイルアプリからのタスク承認リクエストを処理
 * グループタスクの承認フローで使用
 * Cognito認証を前提（middleware: cognito）
 */
class ApproveTaskApiAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected TaskApprovalServiceInterface $taskApprovalService
    ) {}

    /**
     * タスクを承認
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

            // 承認権限チェック（グループタスク作成者のみ）
            if ($task->assigned_by_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'このタスクを承認する権限がありません。',
                ], 403);
            }

            // タスク承認
            $this->taskApprovalService->approveTask($task, $user);

            // レスポンス
            return response()->json([
                'success' => true,
                'message' => 'タスクを承認しました。',
                'data' => [
                    'task' => [
                        'id' => $task->id,
                        'status' => $task->status,
                        'approved_at' => $task->approved_at?->toIso8601String(),
                        'approved_by' => $user->id,
                        'updated_at' => $task->updated_at->toIso8601String(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('API: タスク承認エラー', [
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
