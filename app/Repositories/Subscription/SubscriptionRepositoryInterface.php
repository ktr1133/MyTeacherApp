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
     * @param bool $isMobile モバイルアプリからのリクエストかどうか
     * @return Checkout Laravel Cashier Checkoutオブジェクト
     * @throws \RuntimeException Checkout Session作成失敗時
     */
    public function createCheckoutSession(Group $group, string $plan, int $additionalMembers = 0, bool $isMobile = false): Checkout;

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

    /**
     * サブスクリプションをキャンセル（期間終了時に解約）
     * 
     * @param Subscription $subscription Cashierサブスクリプション
     * @return bool キャンセル成功時true
     */
    public function cancel(Subscription $subscription): bool;

    /**
     * サブスクリプションを即座にキャンセル
     * 
     * @param Subscription $subscription Cashierサブスクリプション
     * @return bool キャンセル成功時true
     */
    public function cancelNow(Subscription $subscription): bool;

    /**
     * サブスクリプションのプランを変更
     * 
     * @param Subscription $subscription Cashierサブスクリプション
     * @param string $newPriceId 新しい価格ID
     * @return bool 変更成功時true
     */
    public function swap(Subscription $subscription, string $newPriceId): bool;

    /**
     * グループの請求履歴を取得
     * 
     * @param Group $group グループ
     * @param int $limit 取得件数
     * @return \Illuminate\Support\Collection 請求履歴のコレクション
     */
    public function getInvoices(Group $group, int $limit = 10): \Illuminate\Support\Collection;

    /**
     * Stripe Billing Portalのセッションを作成
     * 
     * @param Group $group グループ
     * @return string Billing PortalのURL
     */
    public function createBillingPortalSession(Group $group): string;
}
