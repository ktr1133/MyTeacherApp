<?php

namespace App\Http\Actions\Api\Task;

use App\Models\Task;
use App\Services\Task\TaskApprovalServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * API: タスク完了申請アクション
 * 
 * グループタスクの完了申請リクエストを処理
 * 承認必要/不要に応じて適切な処理を実行
 * Cognito認証を前提（middleware: cognito）
 * 
 * 注意: 画像アップロードは UploadTaskImageApiAction を事前に呼び出すこと
 */
class RequestApprovalApiAction
{
    /**
     * コンストラクタ
     *
     * @param TaskApprovalServiceInterface $taskApprovalService タスク承認サービス
     */
    public function __construct(
        private TaskApprovalServiceInterface $taskApprovalService
    ) {}

    /**
     * 完了申請を実行
     *
     * @param Request $request
     * @param Task $task
     * @return JsonResponse
     */
    public function __invoke(Request $request, Task $task): JsonResponse
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

            // 所有権チェック
            if ($task->user_id !== $user->id) {
                Log::warning('[RequestApprovalApiAction] Unauthorized request approval attempt', [
                    'user_id' => $user->id,
                    'task_id' => $task->id,
                    'task_owner_id' => $task->user_id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'このタスクの完了申請権限がありません。',
                ], 403);
            }

            // 承認が必要な場合
            if ($task->requires_approval) {
                // 完了申請する
                $updatedTask = $this->taskApprovalService->requestApproval($task, $user);
                $message = 'タスクの完了を申請しました。承認をお待ちください。';
            } else {
                // 承認が不要な場合は即座に完了
                $updatedTask = $this->taskApprovalService->completeWithoutApproval($task, $user);
                $message = 'タスクを完了しました。';
            }

            Log::info('[RequestApprovalApiAction] Approval requested or completed', [
                'user_id' => $user->id,
                'task_id' => $task->id,
                'requires_approval' => $task->requires_approval,
                'status' => $updatedTask->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'task' => [
                        'id' => $updatedTask->id,
                        'status' => $updatedTask->status,
                        'requires_approval' => $updatedTask->requires_approval,
                        'updated_at' => $updatedTask->updated_at->toIso8601String(),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[RequestApprovalApiAction] Failed to request approval', [
                'user_id' => $request->user()?->id,
                'task_id' => $task->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'タスクの完了申請に失敗しました。',
            ], 500);
        }
    }
}
