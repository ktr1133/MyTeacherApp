<?php

namespace App\Http\Actions\Api\Token;

use App\Services\Token\TokenPurchaseApprovalServiceInterface;
use App\Http\Responders\Api\Token\TokenApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * トークン購入リクエスト承認アクション（API）
 * 
 * 親ユーザーが子どものリクエストを承認し、Stripe決済を実行します。
 */
class ApprovePurchaseRequestApiAction
{
    public function __construct(
        private TokenPurchaseApprovalServiceInterface $service,
        private TokenApiResponder $responder
    ) {}

    /**
     * リクエストを承認
     *
     * @param int $id リクエストID
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(int $id, Request $request): JsonResponse
    {
        $approver = $request->user();

        try {
            // 親ユーザーか確認
            if ($approver->requiresPurchaseApproval()) {
                return $this->responder->error('承認権限がありません。', 403);
            }

            // 承認処理 + Stripe Checkout Session作成
            $result = $this->service->approveRequestWithCheckout($id, $approver);

            Log::info('[ApprovePurchaseRequestApiAction] Request approved', [
                'request_id' => $id,
                'approver_id' => $approver->id,
                'checkout_url' => $result['checkout_url'] ?? null,
            ]);

            return $this->responder->purchaseRequestApproved($result);

        } catch (\Exception $e) {
            Log::error('[ApprovePurchaseRequestApiAction] Failed to approve request', [
                'request_id' => $id,
                'approver_id' => $approver->id,
                'error' => $e->getMessage(),
            ]);

            return $this->responder->error($e->getMessage(), 500);
        }
    }
}
