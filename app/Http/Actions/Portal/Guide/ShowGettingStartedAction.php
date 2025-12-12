<?php

namespace App\Http\Actions\Portal\Guide;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * はじめにページ表示アクション
 * 
 * アカウント登録からログイン、初期設定までの基本操作ガイドを表示
 */
class ShowGettingStartedAction
{
    /**
     * はじめにページを表示
     *
     * @param Request $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        return view('portal.guide.getting-started');
    }
}
