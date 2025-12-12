<?php

namespace App\Http\Actions\Portal\Features;

use Illuminate\View\View;

/**
 * AIアバター機能詳細ページ表示アクション
 */
class ShowAvatarAction
{
    /**
     * AIアバター機能詳細ページを表示
     *
     * @return View
     */
    public function __invoke(): View
    {
        return view('portal.features.avatar');
    }
}
