<?php

declare(strict_types=1);

namespace App\Http\Actions\Admin\Portal;

use App\Models\AppUpdate;
use Illuminate\View\View;

/**
 * アプリ更新履歴編集画面表示アクション
 */
final class EditAppUpdateAction
{
    /**
     * アプリ更新履歴編集画面を表示
     *
     * @param AppUpdate $update
     * @return View
     */
    public function __invoke(AppUpdate $update): View
    {
        return view('admin.portal.updates.edit', compact('update'));
    }
}
