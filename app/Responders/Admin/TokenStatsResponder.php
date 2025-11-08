<?php

namespace App\Responders\Admin;

use Illuminate\Contracts\View\View;

/**
 * トークン統計画面レスポンダー（管理者用）
 */
class TokenStatsResponder
{
    /**
     * レスポンスを返す
     *
     * @param array $data
     * @return View
     */
    public function response(array $data): View
    {
        return view('admin.tokens.stats', $data);
    }
}