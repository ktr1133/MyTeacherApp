<?php

namespace App\Http\Actions\Api\Token;

use App\Http\Responders\Api\Token\TokenApiResponder;
use App\Services\Token\TokenServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: トークン履歴統計取得アクション
 * 
 * GET /api/v1/tokens/history
 * 
 * @package App\Http\Actions\Api\Token
 */
class GetTokenHistoryApiAction
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
     * トークン履歴統計取得処理
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // トークンモードに応じた履歴統計取得
            if ($user->token_mode === 'group' && $user->group_id) {
                // グループ請求モード
                $stats = $this->tokenService->getHistoryStats('App\\Models\\Group', $user->group_id);
            } else {
                // 個人請求モード
                $stats = $this->tokenService->getHistoryStats('App\\Models\\User', $user->id);
            }

            return $this->responder->history($stats);

        } catch (\Exception $e) {
            Log::error('トークン履歴統計取得エラー', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('トークン履歴統計の取得に失敗しました。', 500);
        }
    }
}
