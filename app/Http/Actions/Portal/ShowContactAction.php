<?php

namespace App\Http\Actions\Portal;

use Illuminate\View\View;

/**
 * お問い合わせフォーム表示アクション
 */
class ShowContactAction
{
    /**
     * お問い合わせフォームを表示
     *
     * @return View
     */
    public function __invoke(): View
    {
        return view('portal.contact');
    }
}
