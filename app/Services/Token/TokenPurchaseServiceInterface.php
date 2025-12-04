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
     * @param string $sessionId Checkout Session ID
     * @return bool
     * @throws \Exception
     */
    public function handleCheckoutSessionCompleted(string $sessionId): bool;
    
    /**
     * Payment Intent成功時の処理
     *
     * @param string $paymentIntentId Payment Intent ID
     * @return bool
     * @throws \Exception
     */
    public function handlePaymentIntentSucceeded(string $paymentIntentId): bool;
}
