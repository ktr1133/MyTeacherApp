<?php

namespace App\Http\Actions\Api\Subscription;

use App\Http\Responders\Api\Subscription\SubscriptionApiResponder;
use App\Services\Subscription\SubscriptionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Stripe Billing Portal URL取得Action
 * 
 * エンドポイント: POST /api/subscriptions/billing-portal
 */
class GetBillingPortalUrlAction
{
    /**
     * @param SubscriptionServiceInterface $subscriptionService サブスクリプションサービス
     * @param SubscriptionApiResponder $responder レスポンダー
     */
    public function __construct(
        protected SubscriptionServiceInterface $subscriptionService,
        protected SubscriptionApiResponder $responder
    ) {}

    /**
     * Billing Portal URLを取得
     * 
     * @param Request $request リクエスト
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $group = $user->group;

            // Billing Portalへのアクセスは子どもテーマでも許可
            // （Stripe側で請求書確認・ダウンロードのみ可能、変更操作は親ユーザーが実施）

            // サブスクリプション加入チェック
            if (!$this->subscriptionService->isGroupSubscribed($group)) {
                return $this->responder->noSubscriptionResponse();
            }

            // Billing Portal URL取得
            $portalUrl = $this->subscriptionService->createBillingPortalSession($group);

            return $this->responder->billingPortalResponse($portalUrl);
        } catch (\Exception $e) {
            return $this->responder->errorResponse($e->getMessage(), 500);
        }
    }
}
