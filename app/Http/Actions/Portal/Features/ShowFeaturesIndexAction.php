<?php

namespace App\Http\Actions\Portal\Features;

use Illuminate\View\View;

/**
 * 機能紹介ページ表示アクション
 */
class ShowFeaturesIndexAction
{
    /**
     * 機能紹介メインページを表示
     *
     * @return View
     */
    public function __invoke(): View
    {
        return view('portal.features.index');
    }
}
