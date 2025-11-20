<?php

namespace App\Http\Actions\Admin\Token;

use App\Repositories\Token\TokenRepositoryInterface;
use App\Responders\Admin\TokenUsersResponder;
use Illuminate\Http\Request;

/**
 * ユーザーのトークン一覧表示アクション（管理者用）
 */
class IndexTokenUsersAction
{
    public function __construct(
        private TokenRepositoryInterface $tokenRepository,
        private TokenUsersResponder $responder
    ) {}

    /**
     * ユーザーのトークン一覧を表示
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $balances = $this->tokenRepository->getTokenBalances(20);

        return $this->responder->response([
            'balances' => $balances,
        ]);
    }
}