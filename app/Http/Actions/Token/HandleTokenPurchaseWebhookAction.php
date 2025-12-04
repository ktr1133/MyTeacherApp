<?php

namespace App\Http\Actions\Token;

use App\Services\Token\TokenPurchaseServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

/**
 * トークン購入のStripe Webhook処理アクション
 * 
 * checkout.session.completed, payment_intent.succeeded, payment_intent.payment_failed
 * イベントを処理し、トークン付与を実行
 */
class HandleTokenPurchaseWebhookAction
{
    /**
     * @param TokenPurchaseServiceInterface $tokenPurchaseService トークン購入サービス
     */
    public function __construct(
        protected TokenPurchaseServiceInterface $tokenPurchaseService
    ) {}

    /**
     * Stripe Webhookを処理
     *
     * @param Request $request HTTPリクエスト
     * @return JsonResponse JSONレスポンス
     */
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook.secret');

        // Webhook署名検証
        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            // 無効なペイロード
            Log::error('Webhook: Invalid payload', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            // 署名検証失敗
            Log::error('Webhook: Signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info('Webhook: Event received', [
            'type' => $event->type,
            'id' => $event->id,
        ]);

        // イベントタイプに応じた処理
        try {
            switch ($event->type) {
                case 'checkout.session.completed':
                    $this->handleCheckoutSessionCompleted($event->data->object);
                    break;

                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentIntentFailed($event->data->object);
                    break;

                default:
                    Log::info('Webhook: Unhandled event type', [
                        'type' => $event->type,
                    ]);
            }

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('Webhook: Event handling failed', [
                'type' => $event->type,
                'id' => $event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Stripe側にリトライさせるため500を返す
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * checkout.session.completed イベント処理
     * 
     * Checkout Sessionが完了したらトークンを付与
     *
     * @param object $session Stripe Checkout Session オブジェクト
     * @return void
     * @throws \Exception トークン付与失敗
     */
    protected function handleCheckoutSessionCompleted(object $session): void
    {
        // mode='payment'のチェック（サブスクリプション除外）
        if ($session->mode !== 'payment') {
            Log::info('Webhook: Skipping non-payment Checkout Session', [
                'session_id' => $session->id,
                'mode' => $session->mode,
            ]);
            return;
        }

        // メタデータでトークン購入を判別
        $purchaseType = $session->metadata->purchase_type ?? null;
        if ($purchaseType !== 'token_purchase') {
            Log::info('Webhook: Skipping non-token-purchase session', [
                'session_id' => $session->id,
                'purchase_type' => $purchaseType,
            ]);
            return;
        }

        Log::info('Webhook: Processing Checkout Session completed', [
            'session_id' => $session->id,
            'user_id' => $session->metadata->user_id ?? null,
            'package_id' => $session->metadata->package_id ?? null,
        ]);

        // トークン付与処理
        $this->tokenPurchaseService->handleCheckoutSessionCompleted($session->id);

        Log::info('Webhook: Checkout Session completed successfully', [
            'session_id' => $session->id,
        ]);
    }

    /**
     * payment_intent.succeeded イベント処理
     * 
     * Payment Intentが成功した際の追加処理（必要に応じて）
     *
     * @param object $paymentIntent Stripe Payment Intent オブジェクト
     * @return void
     */
    protected function handlePaymentIntentSucceeded(object $paymentIntent): void
    {
        Log::info('Webhook: Payment Intent succeeded', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount,
            'currency' => $paymentIntent->currency,
        ]);

        // Checkout Session経由の場合はCheckout Session completedで処理済み
        // 直接Payment Intent作成の場合のみここで処理（現在は未実装）
        $this->tokenPurchaseService->handlePaymentIntentSucceeded($paymentIntent->id);
    }

    /**
     * payment_intent.payment_failed イベント処理
     * 
     * 決済失敗時のログ記録
     *
     * @param object $paymentIntent Stripe Payment Intent オブジェクト
     * @return void
     */
    protected function handlePaymentIntentFailed(object $paymentIntent): void
    {
        Log::warning('Webhook: Payment Intent failed', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount,
            'currency' => $paymentIntent->currency,
            'last_payment_error' => $paymentIntent->last_payment_error->message ?? null,
        ]);

        // TODO: ユーザーへの失敗通知（メール等、必要に応じて実装）
    }
}
