<?php

namespace App\Http\Actions\Token;

use App\Services\Payment\PaymentServiceInterface;
use App\Services\Subscription\SubscriptionWebhookServiceInterface;
use App\Services\Token\TokenPurchaseServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stripe Webhook処理アクション
 * 
 * トークン購入とサブスクリプション関連のWebhookを処理する
 */
class HandleStripeWebhookAction extends CashierWebhookController
{
    public function __construct(
        private PaymentServiceInterface $paymentService,
        private SubscriptionWebhookServiceInterface $subscriptionWebhookService,
        private TokenPurchaseServiceInterface $tokenPurchaseService
    ) {}

    /**
     * Handle a Stripe webhook call.
     *
     * @param Request $request
     * @return Response
     */
    public function __invoke(Request $request): Response
    {
        return $this->handleWebhook($request);
    }

    /**
     * Handle payment intent succeeded event.
     *
     * @param array $payload
     * @return void
     */
    protected function handlePaymentIntentSucceeded(array $payload): void
    {
        try {
            $this->paymentService->handlePaymentSucceeded($payload);
        } catch (\Exception $e) {
            Log::error('Webhook: Payment succeeded handling failed', [
                'payment_intent_id' => $payload['data']['object']['id'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle payment intent payment failed event.
     *
     * @param array $payload
     * @return void
     */
    protected function handlePaymentIntentPaymentFailed(array $payload): void
    {
        try {
            $this->paymentService->handlePaymentFailed($payload);
        } catch (\Exception $e) {
            Log::error('Webhook: Payment failed handling failed', [
                'payment_intent_id' => $payload['data']['object']['id'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle checkout session completed event.
     * 
     * @param array $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCheckoutSessionCompleted(array $payload)
    {
        try {
            $sessionData = $payload['data']['object'];
            $mode = $sessionData['mode'] ?? 'unknown';
            $sessionId = $sessionData['id'] ?? 'unknown';
            
            Log::info('Webhook: Checkout session completed', [
                'session_id' => $sessionId,
                'mode' => $mode,
                'metadata' => $sessionData['metadata'] ?? [],
            ]);
            
            // mode='payment' かつ metadata.purchase_type='token_purchase' → トークン購入
            if ($mode === 'payment' && ($sessionData['metadata']['purchase_type'] ?? null) === 'token_purchase') {
                Log::info('Webhook: Processing token purchase', [
                    'session_id' => $sessionId,
                ]);
                
                $this->tokenPurchaseService->handleCheckoutSessionCompleted($sessionId);
                
                Log::info('Webhook: Token purchase completed', [
                    'session_id' => $sessionId,
                ]);
                
                return $this->successMethod();
            }
            
            // mode='subscription' → サブスクリプション処理
            $customerId = $sessionData['customer'] ?? null;
            $subscriptionId = $sessionData['subscription'] ?? null;
            
            if (!$customerId || !$subscriptionId) {
                Log::warning('Webhook: Checkout session completed but missing customer or subscription', [
                    'session_id' => $sessionId,
                    'customer_id' => $customerId,
                    'subscription_id' => $subscriptionId,
                    'mode' => $mode,
                ]);
                return $this->successMethod();
            }
            
            Log::info('Webhook: Subscription checkout completed', [
                'session_id' => $sessionId,
                'customer_id' => $customerId,
                'subscription_id' => $subscriptionId,
            ]);
            
            // Cashierは自動的にcustomer.subscription.createdイベントでsubscriptionsテーブルを更新
            // ここではGroupsテーブルの更新のみ実行（subscription.createdハンドラーでも実行されるが冪等性確保）
            // ※ 実際のsubscription作成はStripeが自動的にcustomer.subscription.createdイベントを発火
            
        } catch (\Exception $e) {
            Log::error('Webhook: Checkout session completed handling failed', [
                'session_id' => $payload['data']['object']['id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        
        return $this->successMethod();
    }

    /**
     * Handle customer subscription created event.
     *
     * @param array $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerSubscriptionCreated(array $payload)
    {
        // デバッグ: Cashierの設定を確認
        Log::info('Webhook: Before parent call', [
            'cashier_model' => config('cashier.model'),
            'customer_id' => $payload['data']['object']['customer'] ?? 'unknown',
            'subscription_id' => $payload['data']['object']['id'] ?? 'unknown',
        ]);
        
        // 親クラスのハンドラーを呼び出してCashierのsubscriptionsテーブルを自動更新
        try {
            $response = parent::handleCustomerSubscriptionCreated($payload);
            Log::info('Webhook: Parent call succeeded');
        } catch (\Exception $e) {
            Log::error('Webhook: Parent call failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $response = $this->successMethod();
        }
        
        // カスタムロジック: Groupsテーブルの更新
        try {
            $this->subscriptionWebhookService->handleSubscriptionCreated($payload);
        } catch (\Exception $e) {
            Log::error('Webhook: Subscription created custom handling failed', [
                'subscription_id' => $payload['data']['object']['id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        
        return $response;
    }

    /**
     * Handle customer subscription updated event.
     *
     * @param array $payload
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    protected function handleCustomerSubscriptionUpdated(array $payload)
    {
        // 親クラスのハンドラーを呼び出してCashierのsubscriptionsテーブルを自動更新
        $response = parent::handleCustomerSubscriptionUpdated($payload);
        
        // カスタムロジック: Groupsテーブルの更新
        try {
            $this->subscriptionWebhookService->handleSubscriptionUpdated($payload);
        } catch (\Exception $e) {
            Log::error('Webhook: Subscription updated custom handling failed', [
                'subscription_id' => $payload['data']['object']['id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        
        return $response;
    }

    /**
     * Handle customer subscription deleted event.
     *
     * @param array $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerSubscriptionDeleted(array $payload)
    {
        // 親クラスのハンドラーを呼び出してCashierのsubscriptionsテーブルを自動更新
        $response = parent::handleCustomerSubscriptionDeleted($payload);
        
        // カスタムロジック: Groupsテーブルの更新
        try {
            $this->subscriptionWebhookService->handleSubscriptionDeleted($payload);
        } catch (\Exception $e) {
            Log::error('Webhook: Subscription deleted custom handling failed', [
                'subscription_id' => $payload['data']['object']['id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        
        return $response;
    }
}