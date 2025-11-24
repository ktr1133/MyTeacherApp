<?php

namespace App\Http\Actions\Token;

use App\Repositories\Token\TokenRepositoryInterface;
use App\Services\Token\TokenServiceInterface;
use App\Responders\Token\TokenHistoryResponder;
use Illuminate\Http\Request;

/**
 * トークン履歴表示アクション
 */
class IndexTokenHistoryAction
{
    public function __construct(
        private TokenRepositoryInterface $tokenRepository,
        private TokenServiceInterface $tokenService,
        private TokenHistoryResponder $responder
    ) {}

    /**
     * トークン履歴画面を表示
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();
        
        // 課金モードに応じてトークン所有者を決定
        if ($user->token_mode === 'group' && $user->group_id) {
            $tokenable = $user->group;
        } else {
            $tokenable = $user;
        }

        $tokenableType = get_class($tokenable);
        $tokenableId = $tokenable->id;

        // トークン残高取得
        $balance = $tokenable->getOrCreateTokenBalance();

        // トークン履歴取得
        $transactions = $this->tokenRepository->getTransactions(
            $tokenableType,
            $tokenableId,
            20
        );

        // 統計データ取得(Serviceから)
        $stats = $this->tokenService->getHistoryStats($tokenableType, $tokenableId);

        return $this->responder->response([
            'balance' => $balance,
            'transactions' => $transactions,
            'monthlyPurchaseAmount' => $stats['monthlyPurchaseAmount'],
            'monthlyPurchaseTokens' => $stats['monthlyPurchaseTokens'],
            'monthlyUsage' => $stats['monthlyUsage'],
        ]);
    }
}