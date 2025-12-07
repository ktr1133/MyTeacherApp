<?php

namespace App\Http\Actions\Api\Token;

use App\Services\Token\TokenPurchaseApprovalServiceInterface;
use App\Http\Responders\Api\Token\TokenApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * トークン購入リクエスト一覧取得アクション（API）
 * 
 * 子ども: 自分のリクエスト一覧
 * 親: 子どもたちのリクエスト一覧
 */
class GetPurchaseRequestsApiAction
{
    public function __construct(
        private TokenPurchaseApprovalServiceInterface $service,
        private TokenApiResponder $responder
    ) {}

    /**
     * 承認待ちリクエスト一覧を取得
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            // 子どもの場合: 自分のリクエスト
            if ($user->requiresPurchaseApproval()) {
                $requests = $this->service->getPendingRequests($user);
            }
            // 親の場合: 子どもたちのリクエスト
            else {
                $requests = $this->service->getPendingRequestsForParent($user);
            }

            Log::info('[GetPurchaseRequestsApiAction] Requests retrieved', [
                'user_id' => $user->id,
                'is_child' => $user->requiresPurchaseApproval(),
                'count' => $requests->count(),
            ]);

            return $this->responder->purchaseRequests($requests);

        } catch (\Exception $e) {
            Log::error('[GetPurchaseRequestsApiAction] Failed to retrieve requests', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->responder->error('リクエスト一覧の取得に失敗しました。', 500);
        }
    }
}
