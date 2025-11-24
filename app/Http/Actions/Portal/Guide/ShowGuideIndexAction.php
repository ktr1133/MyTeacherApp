<?php

namespace App\Http\Actions\Portal\Guide;

use Illuminate\View\View;

/**
 * 使い方ガイドトップ表示アクション
 */
class ShowGuideIndexAction
{
    /**
     * 使い方ガイドトップを表示
     *
     * @return View
     */
    public function __invoke(): View
    {
        return view('portal.guide.index');
    }
}
