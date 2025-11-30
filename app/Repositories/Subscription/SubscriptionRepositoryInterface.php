<?php

namespace App\Repositories\Subscription;

use App\Models\Group;
use Laravel\Cashier\Checkout;
use Laravel\Cashier\Subscription;

/**
 * サブスクリプション関連のデータアクセス操作を定義するインターフェース
 */
interface SubscriptionRepositoryInterface
{
    /**
     * Stripe Checkout Sessionを作成
     * 
     * @param Group $group グループ
     * @param string $plan プラン種別（'family' or 'enterprise'）
     * @param int $additionalMembers 追加メンバー数（エンタープライズのみ）
     * @return Checkout Laravel Cashier Checkoutオブジェクト
     * @throws \RuntimeException Checkout Session作成失敗時
     */
    public function createCheckoutSession(Group $group, string $plan, int $additionalMembers = 0): Checkout;

    /**
     * グループの現在のサブスクリプション情報をDBから取得
     * 
     * @param Group $group グループ
     * @return Subscription|null Cashierのサブスクリプションモデル（存在しない場合はnull）
     */
    public function getCurrentSubscription(Group $group): ?Subscription;

    /**
     * サブスクリプションがアクティブかチェック
     * 
     * @param Subscription $subscription Cashierサブスクリプション
     * @return bool アクティブな場合true
     */
    public function isSubscriptionActive(Subscription $subscription): bool;
}
