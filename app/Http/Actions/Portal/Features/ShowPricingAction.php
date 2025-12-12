<?php

namespace App\Http\Actions\Portal\Features;

use Illuminate\View\View;

/**
 * 料金プラン詳細ページ表示アクション
 */
class ShowPricingAction
{
    /**
     * 料金プラン詳細ページを表示
     *
     * @return View
     */
    public function __invoke(): View
    {
        return view('portal.features.pricing');
    }
}
