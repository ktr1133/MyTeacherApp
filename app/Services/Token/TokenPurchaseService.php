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
     * 
     * Webhookペイロードから直接データを取得（Stripe API呼び出し不要）
     * 
     * Stripe Checkout Session Object Reference:
     * https://docs.stripe.com/api/checkout/sessions/object
     * 
     * Expected payload structure (Stripe API version 2023-10-16+):
     * [
     *   'id' => 'cs_test_...',                      // Session ID (string)
     *   'metadata' => [                             // Custom metadata (object)
     *     'user_id' => '123',                       // User ID (string)
     *     'package_id' => '456',                    // Package ID (string)
     *     'token_amount' => '100000',               // Token amount (string)
     *     'purchase_type' => 'token_purchase',      // Purchase type (string)
     *   ],
     *   'client_reference_id' => '123',             // Alternative user ID (nullable string)
     *   'payment_intent' => 'pi_test_...',          // PaymentIntent ID (string or expandable object)
     *   'mode' => 'payment',                        // Session mode (enum: payment, subscription, setup)
     * ]
     */
    public function handleCheckoutSessionCompleted(array $sessionData): bool
    {
        DB::beginTransaction();
        
        try {
            // Stripe公式ドキュメント: https://docs.stripe.com/api/checkout/sessions/object#checkout_session_object-id
            $sessionId = $sessionData['id'] ?? null;
            
            // Stripe公式ドキュメント: https://docs.stripe.com/api/checkout/sessions/object#checkout_session_object-metadata
            // メタデータからユーザー・パッケージ情報取得
            // Fallback: client_reference_id (https://docs.stripe.com/api/checkout/sessions/object#checkout_session_object-client_reference_id)
            $userId = $sessionData['metadata']['user_id'] 
                ?? $sessionData['client_reference_id'] 
                ?? null;
            $packageId = $sessionData['metadata']['package_id'] ?? null;
            
            if (!$sessionId || !$userId || !$packageId) {
                Log::error('Missing required fields in Checkout Session payload', [
                    'session_id' => $sessionId,
                    'user_id' => $userId,
                    'package_id' => $packageId,
                    'payload_keys' => array_keys($sessionData),
                ]);
                throw new \Exception('Checkout Session payload incomplete');
            }
            
            $user = User::findOrFail($userId);
            $package = $this->tokenService->findPackageById($packageId);
            
            if (!$package) {
                throw new \Exception("Package not found: {$packageId}");
            }
            
            // Stripe公式ドキュメント: https://docs.stripe.com/api/checkout/sessions/object#checkout_session_object-payment_intent
            // Payment Intent ID取得（stringまたは展開済みオブジェクト）
            $paymentIntentId = is_string($sessionData['payment_intent'] ?? null)
                ? $sessionData['payment_intent']
                : ($sessionData['payment_intent']['id'] ?? null);
            
            if (!$paymentIntentId) {
                throw new \Exception('Payment Intent ID not found in session data');
            }
            
            // トークン付与処理
            $this->tokenService->purchaseTokens(
                $user,
                $package,
                'stripe_card',
                $paymentIntentId,
                $package
            );
            
            DB::commit();
            
            Log::info('Checkout Session completed (Webhook payload direct processing)', [
                'session_id' => $sessionId,
                'user_id' => $userId,
                'package_id' => $packageId,
                'tokens' => $package->token_amount,
                'payment_intent_id' => $paymentIntentId,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to handle Checkout Session completed', [
                'session_id' => $sessionData['id'] ?? 'unknown',
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
