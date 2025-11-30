<?php

namespace App\Services\Subscription;

/**
 * Stripe WebhookハンドラーServiceインターフェース
 * 
 * サブスクリプション関連のWebhookイベントを処理し、
 * グループのサブスクリプション状態を更新する
 */
interface SubscriptionWebhookServiceInterface
{
    /**
     * サブスクリプション作成イベントを処理
     * 
     * @param array $payload Stripe Webhookペイロード
     * @return void
     */
    public function handleSubscriptionCreated(array $payload): void;

    /**
     * サブスクリプション更新イベントを処理
     * 
     * @param array $payload Stripe Webhookペイロード
     * @return void
     */
    public function handleSubscriptionUpdated(array $payload): void;

    /**
     * サブスクリプション削除イベントを処理
     * 
     * @param array $payload Stripe Webhookペイロード
     * @return void
     */
    public function handleSubscriptionDeleted(array $payload): void;
}
