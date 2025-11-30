<?php

namespace App\Repositories\Subscription;

use App\Models\Group;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Checkout;
use Laravel\Cashier\Subscription;
use Stripe\Exception\ApiErrorException;

/**
 * サブスクリプション関連のデータアクセス操作をEloquent ORMで実装する具象クラス
 */
class SubscriptionEloquentRepository implements SubscriptionRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function createCheckoutSession(Group $group, string $plan, int $additionalMembers = 0): Checkout
    {
        try {
            $planConfig = config("const.stripe.subscription_plans.{$plan}");
            
            if (!$planConfig) {
                throw new \RuntimeException("Invalid subscription plan: {$plan}");
            }

            $lineItems = [
                [
                    'price' => $planConfig['price_id'],
                    'quantity' => 1,
                ],
            ];

            // エンタープライズプランで追加メンバーがいる場合
            if ($plan === 'enterprise' && $additionalMembers > 0) {
                $lineItems[] = [
                    'price' => config('const.stripe.additional_member_price_id'),
                    'quantity' => $additionalMembers,
                ];
            }

            $checkoutSession = $group->newSubscription('default', $planConfig['price_id'])
                ->checkout([
                    'success_url' => route('subscriptions.success') . '?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('subscriptions.cancel'),
                    'line_items' => $lineItems,
                    'mode' => 'subscription',
                    'client_reference_id' => $group->id,
                    'metadata' => [
                        'group_id' => $group->id,
                        'subscription_plan' => $plan,
                        'additional_members' => $additionalMembers,
                    ],
                ]);

            Log::info('Stripe Checkout Session created', [
                'group_id' => $group->id,
                'plan' => $plan,
                'session_id' => $checkoutSession->id,
            ]);

            return $checkoutSession;
        } catch (ApiErrorException $e) {
            Log::error('Stripe Checkout Session creation failed', [
                'group_id' => $group->id,
                'plan' => $plan,
                'error' => $e->getMessage(),
            ]);
            
            throw new \RuntimeException('サブスクリプションの作成に失敗しました。時間をおいて再度お試しください。');
        }
    }

    /**
     * @inheritDoc
     */
    public function getCurrentSubscription(Group $group): ?Subscription
    {
        $subscription = $group->subscription('default');
        
        if (!$subscription) {
            return null;
        }

        return $subscription;
    }

    /**
     * @inheritDoc
     */
    public function isSubscriptionActive(Subscription $subscription): bool
    {
        return $subscription->active();
    }

    /**
     * @inheritDoc
     */
    public function cancel(Subscription $subscription): bool
    {
        try {
            $subscription->cancel();
            
            Log::info('Subscription canceled (end of period)', [
                'subscription_id' => $subscription->id,
                'stripe_id' => $subscription->stripe_id,
            ]);
            
            return true;
        } catch (ApiErrorException $e) {
            Log::error('Subscription cancel failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            
            throw new \RuntimeException('サブスクリプションのキャンセルに失敗しました。');
        }
    }

    /**
     * @inheritDoc
     */
    public function cancelNow(Subscription $subscription): bool
    {
        try {
            $subscription->cancelNow();
            
            Log::info('Subscription canceled immediately', [
                'subscription_id' => $subscription->id,
                'stripe_id' => $subscription->stripe_id,
            ]);
            
            return true;
        } catch (ApiErrorException $e) {
            Log::error('Subscription cancel now failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            
            throw new \RuntimeException('サブスクリプションの即時キャンセルに失敗しました。');
        }
    }

    /**
     * @inheritDoc
     */
    public function swap(Subscription $subscription, string $newPriceId): bool
    {
        try {
            $subscription->swap($newPriceId);
            
            Log::info('Subscription plan swapped', [
                'subscription_id' => $subscription->id,
                'stripe_id' => $subscription->stripe_id,
                'new_price_id' => $newPriceId,
            ]);
            
            return true;
        } catch (ApiErrorException $e) {
            Log::error('Subscription swap failed', [
                'subscription_id' => $subscription->id,
                'new_price_id' => $newPriceId,
                'error' => $e->getMessage(),
            ]);
            
            throw new \RuntimeException('プランの変更に失敗しました。');
        }
    }

    /**
     * @inheritDoc
     */
    public function getInvoices(Group $group, int $limit = 10): \Illuminate\Support\Collection
    {
        try {
            return $group->invoices($limit);
        } catch (ApiErrorException $e) {
            Log::error('Failed to fetch invoices', [
                'group_id' => $group->id,
                'error' => $e->getMessage(),
            ]);
            
            return collect();
        }
    }

    /**
     * @inheritDoc
     */
    public function createBillingPortalSession(Group $group): string
    {
        try {
            $response = $group->redirectToBillingPortal(route('subscriptions.manage'));
            
            Log::info('Billing portal session created', [
                'group_id' => $group->id,
            ]);
            
            // redirectToBillingPortalはRedirectResponseを返すので、getTargetUrl()でURLを取得
            return $response->getTargetUrl();
        } catch (ApiErrorException $e) {
            Log::error('Billing portal session creation failed', [
                'group_id' => $group->id,
                'error' => $e->getMessage(),
            ]);
            
            throw new \RuntimeException('請求管理ポータルへのアクセスに失敗しました。');
        }
    }
}
