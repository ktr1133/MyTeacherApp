<?php

namespace App\Http\Actions\Api\Token;

use App\Http\Responders\Api\Token\TokenApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Stripe Checkout成功後のモバイルAPI用エンドポイント（トークン購入）
 * 
 * エンドポイント: GET /api/tokens/success
 */
class HandleCheckoutSuccessApiAction
{
    /**
     * @param TokenApiResponder $responder レスポンダー
     */
    public function __construct(
        protected TokenApiResponder $responder
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
                return $this->responder->error('session_id is required', 400);
            }

            Log::info('Token checkout success received', [
                'session_id' => $sessionId,
                'query_params' => $request->all(),
            ]);

            // Webhookで処理されるため、ここでは成功レスポンスのみ返す
            // WebView側でこのレスポンスを受け取ったら画面遷移
            return response()->json([
                'success' => true,
                'message' => 'トークンの購入が完了しました。',
                'session_id' => $sessionId,
            ]);
        } catch (\Exception $e) {
            Log::error('Token checkout success handler error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->responder->error('エラーが発生しました。', 500);
        }
    }
}
