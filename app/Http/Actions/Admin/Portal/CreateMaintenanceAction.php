<?php

namespace App\Http\Actions\Admin\Portal;

use Illuminate\Contracts\View\View;

/**
 * メンテナンス情報作成画面表示アクション
 */
class CreateMaintenanceAction
{
    /**
     * メンテナンス情報作成画面を表示
     *
     * @return View
     */
    public function __invoke(): View
    {
        return view('admin.portal.maintenances.create');
    }
}
