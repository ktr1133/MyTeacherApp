<?php

namespace App\Http\Actions\Token;

use App\Repositories\Token\TokenRepositoryInterface;
use App\Responders\Token\TokenPurchaseResponder;
use Illuminate\Http\Request;

/**
 * トークン購入画面表示アクション
 */
class IndexTokenPurchaseAction
{
    public function __construct(
        private TokenRepositoryInterface $tokenRepository,
        private TokenPurchaseResponder $responder
    ) {}

    /**
     * トークン購入画面を表示
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $balance = $user->getOrCreateTokenBalance();
        $packages = $this->tokenRepository->getActivePackages();

        return $this->responder->response([
            'balance' => $balance,
            'packages' => $packages,
        ]);
    }
}