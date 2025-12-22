<?php

namespace App\Http\Actions\Api\Token;

use App\Http\Responders\Api\Token\TokenApiResponder;
use App\Repositories\Token\TokenRepositoryInterface;
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
     * @param TokenRepositoryInterface $tokenRepository
     * @param TokenServiceInterface $tokenService
     * @param TokenApiResponder $responder
     */
    public function __construct(
        protected TokenRepositoryInterface $tokenRepository,
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

            // トークンモードに応じたtokenable決定
            if ($user->token_mode === 'group' && $user->group_id) {
                // グループ請求モード
                $tokenableType = 'App\\Models\\Group';
                $tokenableId = $user->group_id;
            } else {
                // 個人請求モード
                $tokenableType = 'App\\Models\\User';
                $tokenableId = $user->id;
            }

            // 履歴統計取得
            $stats = $this->tokenService->getHistoryStats($tokenableType, $tokenableId);

            // トランザクション一覧取得（購入履歴のみ）
            $transactions = $this->tokenRepository->getTransactions($tokenableType, $tokenableId, 50);

            return $this->responder->history([
                'monthlyPurchaseAmount' => $stats['monthlyPurchaseAmount'],
                'monthlyPurchaseTokens' => $stats['monthlyPurchaseTokens'],
                'monthlyUsage' => $stats['monthlyUsage'],
                'transactions' => $transactions,
            ]);

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
