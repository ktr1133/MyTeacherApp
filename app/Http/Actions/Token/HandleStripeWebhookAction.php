<?php

namespace App\Http\Actions\Token;

use App\Services\Payment\PaymentServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;

/**
 * Stripe Webhook処理アクション
 */
class HandleStripeWebhookAction extends CashierWebhookController
{
    public function __construct(
        private PaymentServiceInterface $paymentService
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
}