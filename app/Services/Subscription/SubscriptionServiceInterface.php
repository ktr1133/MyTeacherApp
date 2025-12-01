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

    /**
     * サブスクリプションをキャンセル（期間終了時に解約）
     * 
     * @param Group $group グループ
     * @return bool キャンセル成功時true
     * @throws \RuntimeException キャンセル失敗時
     */
    public function cancelSubscription(Group $group): bool;

    /**
     * サブスクリプションを即座にキャンセル
     * 
     * @param Group $group グループ
     * @return bool キャンセル成功時true
     * @throws \RuntimeException キャンセル失敗時
     */
    public function cancelSubscriptionNow(Group $group): bool;

    /**
     * サブスクリプションのプランを変更
     * 
     * @param Group $group グループ
     * @param string $newPlan 新しいプラン種別
     * @param int $additionalMembers 追加メンバー数（エンタープライズのみ）
     * @return bool 変更成功時true
     * @throws \RuntimeException プラン変更失敗時
     */
    public function updateSubscriptionPlan(Group $group, string $newPlan, int $additionalMembers = 0): bool;

    /**
     * 請求履歴を整形して返す
     * 
     * @param Group $group グループ
     * @param int $limit 取得件数（デフォルト10件）
     * @return array 請求履歴の配列
     */
    public function getInvoiceHistory(Group $group, int $limit = 10): array;

    /**
     * Stripe Billing Portalのセッションを作成
     * 
     * @param Group $group グループ
     * @return string Billing PortalのURL
     * @throws \RuntimeException セッション作成失敗時
     */
    public function createBillingPortalSession(Group $group): string;

    /**
     * グループがサブスクリプション加入済みかチェック
     * 
     * @param Group $group チェック対象グループ
     * @return bool サブスク加入済みの場合true
     */
    public function isGroupSubscribed(Group $group): bool;

    /**
     * サブスクリプション限定機能（期間選択・メンバー選択・アバターイベント）へのアクセス権限チェック
     * 
     * @param Group $group チェック対象グループ
     * @return bool 有料機能利用可能な場合true
     */
    public function canAccessSubscriptionFeatures(Group $group): bool;

    /**
     * 月次レポート機能へのアクセス権限チェック
     * 初月は無料、2ヶ月目以降はサブスク必須
     * 
     * @param Group $group チェック対象グループ
     * @return bool 月次レポート閲覧可能な場合true
     */
    public function canAccessMonthlyReport(Group $group): bool;

    /**
     * 過去の特定月レポートへのアクセス権限チェック
     * 初月レポートは無料、それ以降はサブスク必須
     * 
     * @param Group $group チェック対象グループ
     * @param \Carbon\Carbon $reportMonth チェック対象の月
     * @return bool 指定月レポート閲覧可能な場合true
     */
    public function canAccessPastReport(Group $group, \Carbon\Carbon $reportMonth): bool;

    /**
     * 実績画面の期間選択制限チェック
     * 無料: 週間のみ / サブスク: すべて選択可能
     * 
     * @param Group $group チェック対象グループ
     * @param string $period 期間種別（'week', 'month', 'year'）
     * @return bool 指定期間選択可能な場合true
     */
    public function canSelectPeriod(Group $group, string $period): bool;

    /**
     * 実績画面のメンバー選択制限チェック
     * 無料: グループ全体のみ / サブスク: 個人別選択可能
     * 
     * @param Group $group チェック対象グループ
     * @param bool $individualSelection 個人別選択フラグ
     * @return bool 指定選択が可能な場合true
     */
    public function canSelectMember(Group $group, bool $individualSelection): bool;

    /**
     * 実績画面の期間ナビゲーション制限チェック
     * 無料: 当週のみ / サブスク: 過去期間の閲覧可能
     * 
     * @param Group $group チェック対象グループ
     * @param \Carbon\Carbon $targetPeriod チェック対象の期間
     * @return bool 指定期間へのナビゲーション可能な場合true
     */
    public function canNavigateToPeriod(Group $group, \Carbon\Carbon $targetPeriod): bool;

    /**
     * サブスクリプション促進アラートの表示要否を判定
     * 
     * @param Group $group チェック対象グループ
     * @param string $feature 機能名（'period', 'member', 'navigation', 'avatar'）
     * @return bool アラート表示が必要な場合true
     */
    public function shouldShowSubscriptionAlert(Group $group, string $feature): bool;
}
