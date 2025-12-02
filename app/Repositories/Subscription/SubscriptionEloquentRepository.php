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

            // メタデータを定義（CheckoutSessionとSubscriptionの両方に適用）
            $metadata = [
                'group_id' => (string) $group->id,
                'plan' => $plan,  // SubscriptionWebhookServiceが期待するキー名
                'subscription_plan' => $plan,  // 互換性のため両方設定
                'additional_members' => (string) $additionalMembers,
            ];

            // サブスクリプションビルダーを作成
            $subscription = $group->newSubscription('default', $planConfig['price_id']);

            // エンタープライズプランで追加メンバーがいる場合は追加価格を含める
            if ($plan === 'enterprise' && $additionalMembers > 0) {
                $additionalPriceId = config('const.stripe.additional_member_price_id');
                if ($additionalPriceId) {
                    $subscription->price($additionalPriceId, $additionalMembers);
                }
            }

            // チェックアウトセッションを作成
            // メタデータをSubscriptionに設定（Cashierの内部でsubscription_dataに変換される）
            $checkoutSession = $subscription
                ->withMetadata($metadata)  // これがsubscription_data.metadataとして送信される
                ->checkout([
                    'success_url' => route('subscriptions.success') . '?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('subscriptions.cancel'),
                    'client_reference_id' => (string) $group->id,
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
        // userリレーションをEager Loadingして取得
        // Cashierの内部メソッドがuser->stripe()を呼び出すため必須
        return Subscription::where('type', 'default')
            ->where('user_id', $group->master_user_id)
            ->with('user')
            ->first();
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
            
            // Stripeから最新の情報を同期してends_atを更新
            $subscription->refresh();
            
            Log::info('Subscription canceled (end of period)', [
                'subscription_id' => $subscription->id,
                'stripe_id' => $subscription->stripe_id,
                'ends_at' => $subscription->ends_at,
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
