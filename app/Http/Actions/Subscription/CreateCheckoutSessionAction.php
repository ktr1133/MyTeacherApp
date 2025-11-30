<?php

namespace App\Http\Actions\Subscription;

use App\Http\Requests\Subscription\CreateCheckoutSessionRequest;
use App\Services\Subscription\SubscriptionServiceInterface;
use App\Http\Responders\Subscription\SubscriptionResponder;
use Illuminate\Http\RedirectResponse;

class CreateCheckoutSessionAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected SubscriptionServiceInterface $subscriptionService,
        protected SubscriptionResponder $responder
    ) {}

    /**
     * Stripe Checkout Sessionを作成してリダイレクト
     * 
     * @param CreateCheckoutSessionRequest $request
     * @return RedirectResponse
     */
    public function __invoke(CreateCheckoutSessionRequest $request): RedirectResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;

            // グループ管理権限チェック
            if (!$this->subscriptionService->canManageSubscription($group)) {
                return $this->responder->error('サブスクリプション管理の権限がありません。');
            }

            // Checkout Session作成
            $checkoutSession = $this->subscriptionService->createCheckoutSession(
                $group,
                $request->validated('plan'),
                $request->validated('additional_members', 0)
            );

            // Stripeの決済ページへリダイレクト
            return $this->responder->redirectToCheckout($checkoutSession->url);
        } catch (\Exception $e) {
            return $this->responder->error($e->getMessage());
        }
    }
}
