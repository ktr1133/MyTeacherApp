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
        $user = \Illuminate\Support\Facades\Auth::user();
        
        if (!$user) {
            return false;
        }

        // グループ管理者または編集権限を持つユーザー
        return $user->id === $group->master_user_id || $user->group_edit_flg;
    }

    /**
     * @inheritDoc
     */
    public function cancelSubscription(Group $group): bool
    {
        $subscription = $this->repository->getCurrentSubscription($group);
        
        if (!$subscription) {
            throw new \RuntimeException('有効なサブスクリプションが見つかりません。');
        }

        return $this->repository->cancel($subscription);
    }

    /**
     * @inheritDoc
     */
    public function cancelSubscriptionNow(Group $group): bool
    {
        $subscription = $this->repository->getCurrentSubscription($group);
        
        if (!$subscription) {
            throw new \RuntimeException('有効なサブスクリプションが見つかりません。');
        }

        return $this->repository->cancelNow($subscription);
    }

    /**
     * @inheritDoc
     */
    public function updateSubscriptionPlan(Group $group, string $newPlan, int $additionalMembers = 0): bool
    {
        $subscription = $this->repository->getCurrentSubscription($group);
        
        if (!$subscription) {
            throw new \RuntimeException('有効なサブスクリプションが見つかりません。');
        }

        $planConfig = config("const.stripe.subscription_plans.{$newPlan}");
        
        if (!$planConfig) {
            throw new \RuntimeException("無効なプラン: {$newPlan}");
        }

        return $this->repository->swap($subscription, $planConfig['price_id']);
    }

    /**
     * @inheritDoc
     */
    public function getInvoiceHistory(Group $group, int $limit = 10): array
    {
        $invoices = $this->repository->getInvoices($group, $limit);
        
        // データ整形（Serviceの責務）
        return $invoices->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'date' => $invoice->date()->toDateTimeString(),
                'total' => $invoice->rawTotal(), // フォーマットなしの金額（整数、単位: 最小通貨単位）
                'amount_paid' => $invoice->amount_paid, // 支払済み金額
                'status' => $invoice->status,
                'currency' => $invoice->currency,
                'invoice_pdf' => $invoice->invoice_pdf,
            ];
        })->toArray();
    }

    /**
     * @inheritDoc
     */
    public function createBillingPortalSession(Group $group): string
    {
        return $this->repository->createBillingPortalSession($group);
    }

    /**
     * @inheritDoc
     */
    public function isGroupSubscribed(Group $group): bool
    {
        return $group->subscription_active === true;
    }

    /**
     * サブスクリプション限定機能（期間選択・メンバー選択・アバターイベント）へのアクセス権限チェック
     * 
     * @param Group $group チェック対象グループ
     * @return bool 有料機能利用可能な場合true
     */
    public function canAccessSubscriptionFeatures(Group $group): bool
    {
        return $this->isGroupSubscribed($group);
    }

    /**
     * @inheritDoc
     */
    public function canAccessMonthlyReport(Group $group): bool
    {
        // サブスク加入済みの場合は常に閲覧可能
        if ($this->isGroupSubscribed($group)) {
            return true;
        }

        // 無料ユーザーの場合、グループ作成後1ヶ月以内のみ閲覧可能
        $groupCreatedAt = $group->created_at;
        $oneMonthAfterCreation = $groupCreatedAt->copy()->addMonth();

        return now()->lessThan($oneMonthAfterCreation);
    }

    /**
     * @inheritDoc
     */
    public function canAccessPastReport(Group $group, \Carbon\Carbon $reportMonth): bool
    {
        // サブスク加入済みの場合は全期間閲覧可能
        if ($this->isGroupSubscribed($group)) {
            return true;
        }

        // 無料ユーザーの場合、グループ作成月のレポートのみ閲覧可能
        $groupCreatedMonth = $group->created_at->copy()->startOfMonth();
        $reportMonthStart = $reportMonth->copy()->startOfMonth();

        $canAccess = $reportMonthStart->equalTo($groupCreatedMonth);

        if (!$canAccess) {
            \Illuminate\Support\Facades\Log::info('Past report access denied (subscription required)', [
                'group_id' => $group->id,
                'report_month' => $reportMonth->format('Y-m'),
                'group_created_month' => $groupCreatedMonth->format('Y-m'),
            ]);
        }

        return $canAccess;
    }

    /**
     * @inheritDoc
     */
    public function canSelectPeriod(Group $group, string $period): bool
    {
        // サブスク加入済みの場合はすべての期間選択可能
        if ($this->isGroupSubscribed($group)) {
            return true;
        }

        // 無料ユーザーは週間のみ選択可能
        $canSelect = $period === 'week';

        if (!$canSelect) {
            \Illuminate\Support\Facades\Log::info('Period selection denied (subscription required)', [
                'group_id' => $group->id,
                'requested_period' => $period,
            ]);
        }

        return $canSelect;
    }

    /**
     * @inheritDoc
     */
    public function canSelectMember(Group $group, bool $individualSelection): bool
    {
        // サブスク加入済みの場合は個人別選択可能
        if ($this->isGroupSubscribed($group)) {
            return true;
        }

        // 無料ユーザーはグループ全体のみ選択可能
        $canSelect = !$individualSelection;

        if (!$canSelect) {
            \Illuminate\Support\Facades\Log::info('Individual member selection denied (subscription required)', [
                'group_id' => $group->id,
            ]);
        }

        return $canSelect;
    }

    /**
     * @inheritDoc
     */
    public function canNavigateToPeriod(Group $group, \Carbon\Carbon $targetPeriod): bool
    {
        // サブスク加入済みの場合は全期間ナビゲーション可能
        if ($this->isGroupSubscribed($group)) {
            return true;
        }

        // 無料ユーザーは当週のみナビゲーション可能
        $currentWeekStart = now()->startOfWeek();
        $targetWeekStart = $targetPeriod->copy()->startOfWeek();

        $canNavigate = $targetWeekStart->equalTo($currentWeekStart);

        if (!$canNavigate) {
            \Illuminate\Support\Facades\Log::info('Period navigation denied (subscription required)', [
                'group_id' => $group->id,
                'target_period' => $targetPeriod->format('Y-m-d'),
                'current_week_start' => $currentWeekStart->format('Y-m-d'),
            ]);
        }

        return $canNavigate;
    }

    /**
     * @inheritDoc
     */
    public function shouldShowSubscriptionAlert(Group $group, string $feature): bool
    {
        // サブスク加入済みの場合はアラート不要
        if ($this->isGroupSubscribed($group)) {
            return false;
        }

        // 無料ユーザーに対して機能ごとにアラート表示判定
        $showAlert = match ($feature) {
            'period' => true,      // 月間・年間選択時にアラート
            'member' => true,      // 個人別選択時にアラート
            'navigation' => true,  // 過去週への移動時にアラート
            'avatar' => false,     // アバターは非表示のみ（アラート不要）
            default => false,
        };

        if ($showAlert) {
            \Illuminate\Support\Facades\Log::info('Subscription alert triggered', [
                'group_id' => $group->id,
                'feature' => $feature,
            ]);
        }

        return $showAlert;
    }
}
