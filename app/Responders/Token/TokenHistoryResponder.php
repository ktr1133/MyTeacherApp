<?php

namespace App\Responders\Token;

use Illuminate\Contracts\View\View;

/**
 * トークン履歴画面レスポンダー
 */
class TokenHistoryResponder
{
    /**
     * レスポンスを返す
     *
     * @param array $data
     * @return View
     */
    public function response(array $data): View
    {
        return view('tokens.history', $data);
    }
}