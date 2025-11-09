<?php

namespace App\Responders\Admin;

use Illuminate\Contracts\View\View;

/**
 * ユーザーのトークン一覧画面レスポンダー（管理者用）
 */
class TokenUsersResponder
{
    /**
     * レスポンスを返す
     *
     * @param array $data
     * @return View
     */
    public function response(array $data): View
    {
        return view('admin.tokens.users', $data);
    }
}