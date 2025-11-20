<?php

namespace App\Services\Payment;

use App\Models\User;
use App\Models\TokenPackage;
use App\Models\PaymentHistory;
use App\Repositories\Token\TokenRepositoryInterface;
use App\Services\Token\TokenServiceInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * 決済サービス インターフェース
 */
interface PaymentServiceInterface
{
    /**
     * トークン購入の決済を処理
     *
     * @param User $user
     * @param TokenPackage $package
     * @param string $paymentMethodId
     * @return array ['success' => bool, 'payment_intent' => object|null, 'error' => string|null]
     */
    public function processPurchase(User $user, TokenPackage $package, string $paymentMethodId): array;

    /**
     * Webhook処理: 決済成功
     *
     * @param array $payload
     * @return void
     */
    public function handlePaymentSucceeded(array $payload): void;

    /**
     * Webhook処理: 決済失敗
     *
     * @param array $payload
     * @return void
     */
    public function handlePaymentFailed(array $payload): void;
}