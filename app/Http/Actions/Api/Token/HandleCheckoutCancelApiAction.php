<?php

namespace App\Http\Actions\Api\Token;

use App\Http\Responders\Api\Token\TokenApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Stripe Checkoutキャンセル後のモバイルAPI用エンドポイント（トークン購入）
 * 
 * エンドポイント: GET /api/tokens/cancel
 */
class HandleCheckoutCancelApiAction
{
    /**
     * @param TokenApiResponder $responder レスポンダー
     */
    public function __construct(
        protected TokenApiResponder $responder
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
            Log::info('Token checkout cancel received', [
                'query_params' => $request->all(),
            ]);

            // キャンセル通知のみ返す
            // WebView側でこのレスポンスを受け取ったら画面遷移
            return response()->json([
                'success' => false,
                'message' => 'トークンの購入をキャンセルしました。',
            ]);
        } catch (\Exception $e) {
            Log::error('Token checkout cancel handler error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->responder->error('エラーが発生しました。', 500);
        }
    }
}
