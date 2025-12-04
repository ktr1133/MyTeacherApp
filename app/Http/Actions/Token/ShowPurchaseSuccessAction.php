<?php

namespace App\Http\Actions\Token;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * トークン購入成功ページ表示アクション
 */
class ShowPurchaseSuccessAction
{
    /**
     * 購入成功ページを表示
     *
     * @param Request $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        $sessionId = $request->query('session_id');
        
        return view('tokens.purchase-success', [
            'session_id' => $sessionId,
        ]);
    }
}
