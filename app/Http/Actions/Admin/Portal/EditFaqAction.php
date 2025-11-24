<?php

declare(strict_types=1);

namespace App\Http\Actions\Admin\Portal;

use App\Models\Faq;
use Illuminate\View\View;

/**
 * FAQ編集画面表示アクション
 */
final class EditFaqAction
{
    /**
     * FAQ編集画面を表示
     *
     * @param Faq $faq
     * @return View
     */
    public function __invoke(Faq $faq): View
    {
        return view('admin.portal.faqs.edit', compact('faq'));
    }
}
