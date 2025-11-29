<?php

namespace App\Http\Actions\Api\Task;

use App\Models\Task;
use App\Services\Task\TaskApprovalServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: タスク却下アクション
 * 
 * モバイルアプリからのタスク却下リクエストを処理
 * グループタスクの承認フローで使用
 * Cognito認証を前提（middleware: cognito）
 */
class RejectTaskApiAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected TaskApprovalServiceInterface $taskApprovalService
    ) {}

    /**
     * タスクを却下
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

            // バリデーション
            $validated = $request->validate([
                'reason' => ['nullable', 'string', 'max:500'],
            ]);

            // 承認権限チェック（グループタスク作成者のみ）
            if ($task->assigned_by_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'このタスクを却下する権限がありません。',
                ], 403);
            }

            // タスク却下
            $this->taskApprovalService->rejectTask($task, $user, $validated['reason'] ?? null);

            // レスポンス
            return response()->json([
                'success' => true,
                'message' => 'タスクを却下しました。',
                'data' => [
                    'task' => [
                        'id' => $task->id,
                        'status' => $task->status,
                        'rejected_at' => now()->toIso8601String(),
                        'rejected_by' => $user->id,
                        'rejection_reason' => $validated['reason'] ?? null,
                        'updated_at' => $task->updated_at->toIso8601String(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('API: タスク却下エラー', [
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
