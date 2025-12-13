<?php

namespace App\Http\Actions\Api\Subscription;

use App\Http\Responders\Api\Subscription\SubscriptionApiResponder;
use App\Services\Subscription\SubscriptionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Stripe Checkout成功後のモバイルAPI用エンドポイント
 * 
 * エンドポイント: GET /api/subscriptions/success
 */
class HandleCheckoutSuccessApiAction
{
    /**
     * @param SubscriptionServiceInterface $subscriptionService サブスクリプションサービス
     * @param SubscriptionApiResponder $responder レスポンダー
     */
    public function __construct(
        protected SubscriptionServiceInterface $subscriptionService,
        protected SubscriptionApiResponder $responder
    ) {}

    /**
     * Checkout成功処理
     * 
     * @param Request $request リクエスト
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $sessionId = $request->query('session_id');

            if (!$sessionId) {
                return $this->responder->errorResponse('session_id is required', 400);
            }

            Log::info('Checkout success received', [
                'session_id' => $sessionId,
                'query_params' => $request->all(),
            ]);

            // Webhookで処理されるため、ここでは成功レスポンスのみ返す
            // WebView側でこのレスポンスを受け取ったら画面遷移
            return $this->responder->successResponse([
                'success' => true,
                'message' => 'サブスクリプションの購入が完了しました。',
                'session_id' => $sessionId,
            ]);
        } catch (\Exception $e) {
            Log::error('Checkout success handler error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->responder->errorResponse('エラーが発生しました。', 500);
        }
    }
}
