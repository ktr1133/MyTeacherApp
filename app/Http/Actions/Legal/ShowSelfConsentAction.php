<?php

namespace App\Http\Actions\Legal;

use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * 本人同意画面表示アクション
 * 
 * 13歳到達時の本人同意画面を表示します。
 * Phase 6D: 13歳到達時の本人再同意
 */
class ShowSelfConsentAction
{
    /**
     * 本人同意画面を表示
     * 
     * @return View
     */
    public function __invoke(): View
    {
        return view('legal.self-consent');
    }
}
