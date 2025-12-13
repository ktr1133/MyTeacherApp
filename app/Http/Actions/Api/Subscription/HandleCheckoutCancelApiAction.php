<?php

namespace App\Http\Actions\Api\Subscription;

use App\Http\Responders\Api\Subscription\SubscriptionApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Stripe Checkoutキャンセル後のモバイルAPI用エンドポイント
 * 
 * エンドポイント: GET /api/subscriptions/cancel
 */
class HandleCheckoutCancelApiAction
{
    /**
     * @param SubscriptionApiResponder $responder レスポンダー
     */
    public function __construct(
        protected SubscriptionApiResponder $responder
    ) {}

    /**
     * Checkoutキャンセル処理
     * 
     * @param Request $request リクエスト
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            Log::info('Checkout cancelled', [
                'query_params' => $request->all(),
            ]);

            // WebView側でこのレスポンスを受け取ったら画面遷移
            return $this->responder->successResponse([
                'cancelled' => true,
                'message' => '購入がキャンセルされました。',
            ]);
        } catch (\Exception $e) {
            Log::error('Checkout cancel handler error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->responder->errorResponse('エラーが発生しました。', 500);
        }
    }
}
