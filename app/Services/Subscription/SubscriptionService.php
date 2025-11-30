<?php

namespace App\Services\Subscription;

use App\Models\Group;
use App\Repositories\Subscription\SubscriptionRepositoryInterface;
use Laravel\Cashier\Checkout;

/**
 * サブスクリプション管理のビジネスロジック実装
 * データ取得はRepositoryに委譲し、このServiceは整形のみを行う
 */
class SubscriptionService implements SubscriptionServiceInterface
{
    /**
     * @param SubscriptionRepositoryInterface $repository サブスクリプションリポジトリ
     */
    public function __construct(
        protected SubscriptionRepositoryInterface $repository
    ) {}

    /**
     * @inheritDoc
     */
    public function createCheckoutSession(Group $group, string $plan, int $additionalMembers = 0): Checkout
    {
        // Repository経由でCheckout Session作成（DB操作とStripe API呼び出し）
        return $this->repository->createCheckoutSession($group, $plan, $additionalMembers);
    }

    /**
     * @inheritDoc
     */
    public function getCurrentSubscription(Group $group): ?array
    {
        // Repository経由でサブスクリプション取得
        $subscription = $this->repository->getCurrentSubscription($group);
        
        if (!$subscription) {
            return null;
        }

        // アクティブチェック（Repository経由）
        if (!$this->repository->isSubscriptionActive($subscription)) {
            return null;
        }

        // データ整形（Serviceの責務）
        return [
            'plan' => $group->subscription_plan,
            'active' => $group->subscription_active,
            'stripe_status' => $subscription->stripe_status,
            'ends_at' => $subscription->ends_at,
            'trial_ends_at' => $subscription->trial_ends_at,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAvailablePlans(): array
    {
        // 設定ファイルから取得して返す（整形の必要がないのでそのまま返す）
        return config('const.stripe.subscription_plans');
    }

    /**
     * @inheritDoc
     */
    public function canManageSubscription(Group $group): bool
    {
        // 権限チェックはGroupモデルに関連しないクエリなので、Serviceに残す
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        // グループ管理者または編集権限を持つユーザー
        return $user->id === $group->master_user_id || $user->group_edit_flg;
    }
}
