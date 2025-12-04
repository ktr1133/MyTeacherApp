<?php

namespace App\Services\Token;

use App\Models\User;
use App\Models\TokenPackage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Exception\ApiErrorException;

/**
 * トークン購入サービス実装
 */
class TokenPurchaseService implements TokenPurchaseServiceInterface
{
    public function __construct(
        protected TokenServiceInterface $tokenService
    ) {
        // Stripe APIキーを設定（config/services.phpから取得）
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * {@inheritdoc}
     */
    public function createCheckoutSession(User $user, TokenPackage $package): CheckoutSession
    {
        try {
            $sessionParams = [
                'payment_method_types' => ['card'],
                'mode' => 'payment', // 都度決済（subscription以外）
                'client_reference_id' => (string) $user->id, // ユーザーIDを保存
                'success_url' => route('tokens.purchase.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('tokens.purchase.cancel'),
                'line_items' => [
                    [
                        'price' => $package->stripe_price_id,
                        'quantity' => 1,
                    ],
                ],
                'metadata' => [
                    'user_id' => (string) $user->id,
                    'package_id' => (string) $package->id,
                    'token_amount' => (string) $package->token_amount,
                    'purchase_type' => 'token_purchase',
                ],
            ];
            
            // Stripe顧客IDがあれば指定（再利用）、なければcustomer_emailを設定
            if ($user->hasStripeId()) {
                $sessionParams['customer'] = $user->stripe_id;
            } else {
                $sessionParams['customer_email'] = $user->email;
            }
            
            return CheckoutSession::create($sessionParams);
            
        } catch (ApiErrorException $e) {
            Log::error('Stripe API error in createCheckoutSession', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handleCheckoutSessionCompleted(string $sessionId): bool
    {
        DB::beginTransaction();
        
        try {
            // Checkout Sessionを取得
            $session = CheckoutSession::retrieve([
                'id' => $sessionId,
                'expand' => ['payment_intent'],
            ]);
            
            // メタデータからユーザー・パッケージ情報取得
            $userId = $session->metadata->user_id ?? $session->client_reference_id;
            $packageId = $session->metadata->package_id;
            
            if (!$userId || !$packageId) {
                Log::error('Missing metadata in Checkout Session', [
                    'session_id' => $sessionId,
                    'metadata' => $session->metadata->toArray(),
                ]);
                throw new \Exception('Checkout Session metadata incomplete');
            }
            
            $user = User::findOrFail($userId);
            $package = $this->tokenService->findPackageById($packageId);
            
            if (!$package) {
                throw new \Exception("Package not found: {$packageId}");
            }
            
            // Payment Intent ID取得
            $paymentIntentId = is_string($session->payment_intent) 
                ? $session->payment_intent 
                : $session->payment_intent->id;
            
            // トークン付与処理
            $this->tokenService->purchaseTokens(
                $user,
                $package,
                'stripe_card',
                $paymentIntentId,
                $package
            );
            
            DB::commit();
            
            Log::info('Checkout Session completed', [
                'session_id' => $sessionId,
                'user_id' => $userId,
                'package_id' => $packageId,
                'tokens' => $package->token_amount,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to handle Checkout Session completed', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handlePaymentIntentSucceeded(string $paymentIntentId): bool
    {
        try {
            // Payment Intentを取得
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            
            // メタデータがない場合はCheckout Session経由なので処理不要
            if (empty($paymentIntent->metadata->user_id)) {
                Log::info('Payment Intent without metadata (Checkout Session handled)', [
                    'payment_intent_id' => $paymentIntentId,
                ]);
                return true;
            }
            
            // 直接Payment Intent作成の場合（将来対応）
            Log::info('Payment Intent succeeded (direct)', [
                'payment_intent_id' => $paymentIntentId,
            ]);
            
            // TODO: 直接Payment Intent決済への対応（必要に応じて実装）
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to handle Payment Intent succeeded', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
}
