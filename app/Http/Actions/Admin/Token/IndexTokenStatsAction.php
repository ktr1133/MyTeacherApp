<?php

namespace App\Http\Actions\Admin\Token;

use App\Repositories\Token\TokenRepositoryInterface;
use App\Responders\Admin\TokenStatsResponder;
use Illuminate\Http\Request;

/**
 * トークン統計表示アクション（管理者用）
 */
class IndexTokenStatsAction
{
    public function __construct(
        private TokenRepositoryInterface $tokenRepository,
        private TokenStatsResponder $responder
    ) {}

    /**
     * トークン統計画面を表示
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $stats = $this->tokenRepository->getTokenStats();

        return $this->responder->response([
            'stats' => $stats,
        ]);
    }
}