<?php

namespace App\Http\Actions\Admin\Payment;

use App\Repositories\Token\TokenRepositoryInterface;
use App\Responders\Admin\PaymentHistoryResponder;
use Illuminate\Http\Request;

/**
 * 課金履歴表示アクション（管理者用）
 */
class IndexPaymentHistoryAction
{
    public function __construct(
        private TokenRepositoryInterface $tokenRepository,
        private PaymentHistoryResponder $responder
    ) {}

    /**
     * 課金履歴画面を表示
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $histories = $this->tokenRepository->getPaymentHistories(20);

        return $this->responder->response([
            'histories' => $histories,
        ]);
    }
}