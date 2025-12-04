<?php

namespace App\Services\Token;

use App\Models\User;
use App\Models\TokenPackage;
use Stripe\Checkout\Session as CheckoutSession;

/**
 * トークン購入サービスインターフェース
 */
interface TokenPurchaseServiceInterface
{
    /**
     * Stripe Checkout Sessionを作成
     *
     * @param User $user 購入ユーザー
     * @param TokenPackage $package 購入パッケージ
     * @return CheckoutSession
     * @throws \Exception
     */
    public function createCheckoutSession(User $user, TokenPackage $package): CheckoutSession;
    
    /**
     * Checkout Session完了時の処理
     * 
     * Webhookペイロードから直接データを取得してトークンを付与
     * Stripe API呼び出し不要（パフォーマンス向上、コスト削減）
     *
     * @param array $sessionData Checkout Session data from Webhook payload
     *                           Reference: https://docs.stripe.com/api/checkout/sessions/object
     *                           Expected keys: 'id', 'metadata', 'client_reference_id', 'payment_intent'
     * @return bool
     * @throws \Exception
     */
    public function handleCheckoutSessionCompleted(array $sessionData): bool;
    
    /**
     * Payment Intent成功時の処理
     *
     * @param string $paymentIntentId Payment Intent ID
     * @return bool
     * @throws \Exception
     */
    public function handlePaymentIntentSucceeded(string $paymentIntentId): bool;
}
