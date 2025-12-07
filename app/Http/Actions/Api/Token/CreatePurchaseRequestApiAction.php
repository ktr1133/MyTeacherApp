<?php

namespace App\Http\Actions\Api\Token;

use App\Http\Requests\Api\Token\CreatePurchaseRequestRequest;
use App\Services\Token\TokenPurchaseApprovalServiceInterface;
use App\Http\Responders\Api\Token\TokenApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * トークン購入リクエスト作成アクション（API）
 * 
 * 子どもユーザーがトークン購入のリクエストを送信します。
 */
class CreatePurchaseRequestApiAction
{
    public function __construct(
        private TokenPurchaseApprovalServiceInterface $service,
        private TokenApiResponder $responder
    ) {}

    /**
     * トークン購入リクエストを作成
     *
     * @param CreatePurchaseRequestRequest $request
     * @return JsonResponse
     */
    public function __invoke(CreatePurchaseRequestRequest $request): JsonResponse
    {
        $user = $request->user();
        $packageId = $request->validated()['package_id'];

        try {
            // 子どもユーザーか確認
            if (!$user->requiresPurchaseApproval()) {
                return $this->responder->error('この機能は子どもアカウント専用です。', 403);
            }

            // リクエスト作成
            $purchaseRequest = $this->service->createPurchaseRequest($user, $packageId);

            Log::info('[CreatePurchaseRequestApiAction] Purchase request created', [
                'request_id' => $purchaseRequest->id,
                'user_id' => $user->id,
                'package_id' => $packageId,
            ]);

            return $this->responder->purchaseRequestCreated($purchaseRequest);

        } catch (\Exception $e) {
            Log::error('[CreatePurchaseRequestApiAction] Failed to create purchase request', [
                'user_id' => $user->id,
                'package_id' => $packageId,
                'error' => $e->getMessage(),
            ]);

            return $this->responder->error('購入リクエストの作成に失敗しました。', 500);
        }
    }
}
