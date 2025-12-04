<?php

namespace App\Http\Actions\Token;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * トークン購入キャンセルページ表示アクション
 */
class ShowPurchaseCancelAction
{
    /**
     * 購入キャンセルページを表示
     *
     * @param Request $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        return view('tokens.purchase-cancel');
    }
}
