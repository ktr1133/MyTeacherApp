<?php

namespace App\Http\Actions\Token;

use App\Services\Payment\PaymentServiceInterface;
use App\Services\Subscription\SubscriptionWebhookServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;

/**
 * Stripe Webhook処理アクション
 * 
 * トークン購入とサブスクリプション関連のWebhookを処理する
 */
class HandleStripeWebhookAction extends CashierWebhookController
{
    public function __construct(
        private PaymentServiceInterface $paymentService,
        private SubscriptionWebhookServiceInterface $subscriptionWebhookService
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
     * Handle customer subscription created event.
     *
     * @param array $payload
     * @return void
     */
    protected function handleCustomerSubscriptionCreated(array $payload): void
    {
        try {
            $this->subscriptionWebhookService->handleSubscriptionCreated($payload);
        } catch (\Exception $e) {
            Log::error('Webhook: Subscription created handling failed', [
                'subscription_id' => $payload['data']['object']['id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle customer subscription updated event.
     *
     * @param array $payload
     * @return void
     */
    protected function handleCustomerSubscriptionUpdated(array $payload): void
    {
        try {
            $this->subscriptionWebhookService->handleSubscriptionUpdated($payload);
        } catch (\Exception $e) {
            Log::error('Webhook: Subscription updated handling failed', [
                'subscription_id' => $payload['data']['object']['id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle customer subscription deleted event.
     *
     * @param array $payload
     * @return void
     */
    protected function handleCustomerSubscriptionDeleted(array $payload): void
    {
        try {
            $this->subscriptionWebhookService->handleSubscriptionDeleted($payload);
        } catch (\Exception $e) {
            Log::error('Webhook: Subscription deleted handling failed', [
                'subscription_id' => $payload['data']['object']['id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}