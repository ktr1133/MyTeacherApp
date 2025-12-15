<?php

namespace App\Http\Actions\Api\Task;

use App\Services\Approval\ApprovalMergeServiceInterface;
use App\Services\Task\TaskApprovalServiceInterface;
use App\Services\Token\TokenPurchaseApprovalServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * API: 承認待ち一覧取得アクション
 * 
 * 承認者向けの承認待ちタスク・トークン購入リクエスト一覧を取得
 * タスク承認とトークン購入承認を統合して返却
 * Cognito認証を前提（middleware: cognito）
 */
class ListPendingApprovalsApiAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        private TaskApprovalServiceInterface $taskApprovalService,
        private TokenPurchaseApprovalServiceInterface $tokenPurchaseApprovalService,
        private ApprovalMergeServiceInterface $approvalMergeService
    ) {}

    /**
     * 承認待ち一覧を取得
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

            // ページネーションパラメータ
            $perPage = min((int) $request->query('per_page', 15), 50); // 最大50件
            $page = (int) $request->query('page', 1);

            // タスクの承認待ちデータを取得
            $pendingTasks = $this->taskApprovalService->getPendingTasksForApprover($user);

            // トークン購入の承認待ちデータを取得
            $pendingTokenPurchases = $this->tokenPurchaseApprovalService->getPendingRequestsForParent($user);

            // データを統合してページネーション
            $approvals = $this->approvalMergeService->mergeAndSortApprovals(
                $pendingTasks,
                $pendingTokenPurchases,
                $perPage
            );

            Log::info('[ListPendingApprovalsApiAction] Retrieved pending approvals', [
                'user_id' => $user->id,
                'total_count' => $approvals->total(),
                'page' => $page,
            ]);

            // レスポンス整形
            return response()->json([
                'success' => true,
                'data' => [
                    'approvals' => collect($approvals->items())->map(function ($approval) {
                        // $approval は配列形式で返される
                        $type = $approval['type'];
                        $model = $approval['model'];
                        
                        if ($type === 'task') {
                            return [
                                'type' => 'task',
                                'id' => $approval['id'],
                                'title' => $approval['title'],
                                'description' => $approval['description'] ?? null,
                                'requester' => [
                                    'id' => $model->user->id,
                                    'username' => $model->user->username,
                                    'name' => $model->user->name,
                                ],
                                'requested_at' => $approval['requested_at']->toIso8601String(),
                                'metadata' => [
                                    'reward' => $approval['reward'],
                                    'has_images' => $approval['has_images'],
                                    'images_count' => $approval['images_count'],
                                ],
                            ];
                        } else {
                            // token_purchase
                            return [
                                'type' => 'token_purchase',
                                'id' => $approval['id'],
                                'title' => $approval['package_name'],
                                'description' => null,
                                'requester' => [
                                    'id' => $model->user->id,
                                    'username' => $model->user->username,
                                    'name' => $model->user->name,
                                ],
                                'requested_at' => $approval['requested_at']->toIso8601String(),
                                'metadata' => [
                                    'token_amount' => $approval['token_amount'],
                                    'price' => $approval['price'],
                                ],
                            ];
                        }
                    }),
                    'pagination' => [
                        'current_page' => $approvals->currentPage(),
                        'per_page' => $approvals->perPage(),
                        'total' => $approvals->total(),
                        'last_page' => $approvals->lastPage(),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[ListPendingApprovalsApiAction] Failed to retrieve pending approvals', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '承認待ち一覧の取得に失敗しました。',
            ], 500);
        }
    }
}
