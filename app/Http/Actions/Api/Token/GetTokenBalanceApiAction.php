<?php

namespace App\Http\Actions\Api\Token;

use App\Http\Responders\Api\Token\TokenApiResponder;
use App\Services\Token\TokenServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: トークン残高取得アクション
 * 
 * GET /api/v1/tokens/balance
 * 
 * @package App\Http\Actions\Api\Token
 */
class GetTokenBalanceApiAction
{
    /**
     * コンストラクタ
     *
     * @param TokenServiceInterface $tokenService
     * @param TokenApiResponder $responder
     */
    public function __construct(
        protected TokenServiceInterface $tokenService,
        protected TokenApiResponder $responder
    ) {}

    /**
     * トークン残高取得処理
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // トークンモードに応じた残高取得
            if ($user->token_mode === 'group' && $user->group_id) {
                // グループ請求モード
                $balance = $this->tokenService->getOrCreateBalance('App\\Models\\Group', $user->group_id);
            } else {
                // 個人請求モード
                $balance = $this->tokenService->getOrCreateBalance('App\\Models\\User', $user->id);
            }

            return $this->responder->balance($balance);

        } catch (\Exception $e) {
            Log::error('トークン残高取得エラー', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('トークン残高の取得に失敗しました。', 500);
        }
    }
}
