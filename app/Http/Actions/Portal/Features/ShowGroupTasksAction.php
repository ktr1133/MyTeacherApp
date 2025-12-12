<?php

namespace App\Http\Actions\Portal\Features;

use Illuminate\View\View;

/**
 * グループタスク機能詳細ページ表示アクション
 */
class ShowGroupTasksAction
{
    /**
     * グループタスク機能詳細ページを表示
     *
     * @return View
     */
    public function __invoke(): View
    {
        return view('portal.features.group-tasks');
    }
}
