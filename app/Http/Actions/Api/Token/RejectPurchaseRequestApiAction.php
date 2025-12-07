<?php

namespace App\Http\Actions\Api\Token;

use App\Services\Token\TokenPurchaseApprovalServiceInterface;
use App\Http\Responders\Api\Token\TokenApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * トークン購入リクエスト却下アクション（API）
 * 
 * 親ユーザーが子どものリクエストを却下します。
 */
class RejectPurchaseRequestApiAction
{
    public function __construct(
        private TokenPurchaseApprovalServiceInterface $service,
        private TokenApiResponder $responder
    ) {}

    /**
     * リクエストを却下
     *
     * @param int $id リクエストID
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(int $id, Request $request): JsonResponse
    {
        $approver = $request->user();
        $reason = $request->input('reason');

        try {
            // 親ユーザーか確認
            if ($approver->requiresPurchaseApproval()) {
                return $this->responder->error('承認権限がありません。', 403);
            }

            // 却下処理
            $purchaseRequest = $this->service->rejectRequest($id, $approver, $reason);

            Log::info('[RejectPurchaseRequestApiAction] Request rejected', [
                'request_id' => $id,
                'approver_id' => $approver->id,
                'reason' => $reason,
            ]);

            return $this->responder->purchaseRequestRejected($purchaseRequest);

        } catch (\Exception $e) {
            Log::error('[RejectPurchaseRequestApiAction] Failed to reject request', [
                'request_id' => $id,
                'approver_id' => $approver->id,
                'error' => $e->getMessage(),
            ]);

            return $this->responder->error($e->getMessage(), 500);
        }
    }
}
