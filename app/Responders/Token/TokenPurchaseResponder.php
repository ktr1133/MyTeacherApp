<?php

namespace App\Responders\Token;

use Illuminate\Contracts\View\View;

/**
 * トークン購入画面レスポンダー
 */
class TokenPurchaseResponder
{
    /**
     * レスポンスを返す
     *
     * @param array $data
     * @return View
     */
    public function response(array $data): View
    {
        return view('tokens.purchase', $data);
    }
}