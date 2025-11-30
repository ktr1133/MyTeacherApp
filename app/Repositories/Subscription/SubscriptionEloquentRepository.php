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
}
