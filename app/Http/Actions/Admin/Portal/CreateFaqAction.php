<?php

declare(strict_types=1);

namespace App\Http\Actions\Admin\Portal;

use Illuminate\View\View;

/**
 * FAQ作成画面表示アクション
 */
final class CreateFaqAction
{
    /**
     * FAQ作成画面を表示
     *
     * @return View
     */
    public function __invoke(): View
    {
        return view('admin.portal.faqs.create');
    }
}
