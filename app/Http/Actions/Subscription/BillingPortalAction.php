<?php

namespace App\Http\Actions\Subscription;

use App\Http\Responders\Subscription\SubscriptionResponder;
use App\Services\Subscription\SubscriptionServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Stripe Billing Portal リダイレクトアクション
 */
class BillingPortalAction
{
    /**
     * @param SubscriptionServiceInterface $subscriptionService サブスクリプションサービス
     * @param SubscriptionResponder $responder レスポンダー
     */
    public function __construct(
        protected SubscriptionServiceInterface $subscriptionService,
        protected SubscriptionResponder $responder
    ) {}

    /**
     * Stripe Billing Portalにリダイレクト
     * 
     * @param Request $request HTTPリクエスト
     * @return RedirectResponse
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();
        $group = $user->group;

        // グループが存在しない場合
        if (!$group) {
            return $this->responder->error('グループが存在しません。');
        }

        // 管理権限チェック
        if (!$this->subscriptionService->canManageSubscription($group)) {
            abort(403, 'サブスクリプションの管理権限がありません。');
        }

        try {
            $portalUrl = $this->subscriptionService->createBillingPortalSession($group);
            
            return $this->responder->redirectToBillingPortal($portalUrl);
        } catch (\RuntimeException $e) {
            Log::error('Billing portal redirect failed', [
                'group_id' => $group->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('請求管理ポータルへのアクセスに失敗しました。時間をおいて再度お試しください。');
        }
    }
}
