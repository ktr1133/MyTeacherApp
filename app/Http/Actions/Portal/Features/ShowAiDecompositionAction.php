<?php

namespace App\Http\Actions\Portal\Features;

use Illuminate\View\View;

/**
 * AIタスク分解機能詳細ページ表示アクション
 */
class ShowAiDecompositionAction
{
    /**
     * AIタスク分解機能詳細ページを表示
     *
     * @return View
     */
    public function __invoke(): View
    {
        return view('portal.features.ai-decomposition');
    }
}
