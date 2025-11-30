<?php

namespace App\Services\Subscription;

use App\Models\Group;
use Laravel\Cashier\Checkout;

/**
 * サブスクリプション管理のビジネスロジックを定義するインターフェース
 * データ取得はRepositoryに委譲し、Serviceは整形のみを行う
 */
interface SubscriptionServiceInterface
{
    /**
     * Stripe Checkout Sessionを作成（Repository経由）
     * 
     * @param Group $group グループ
     * @param string $plan プラン種別（'family' or 'enterprise'）
     * @param int $additionalMembers 追加メンバー数（エンタープライズのみ）
     * @return Checkout Laravel Cashier Checkoutオブジェクト
     * @throws \RuntimeException Checkout Session作成失敗時
     */
    public function createCheckoutSession(Group $group, string $plan, int $additionalMembers = 0): Checkout;

    /**
     * グループの現在のサブスクリプション情報を整形して返す
     * 
     * @param Group $group グループ
     * @return array|null サブスクリプション情報（存在しない場合はnull）
     */
    public function getCurrentSubscription(Group $group): ?array;

    /**
     * サブスクリプションプラン情報を整形して返す
     * 
     * @return array プラン情報の配列
     */
    public function getAvailablePlans(): array;

    /**
     * グループがサブスクリプション管理可能かチェック
     * 
     * @param Group $group グループ
     * @return bool 管理可能な場合true
     */
    public function canManageSubscription(Group $group): bool;
}
