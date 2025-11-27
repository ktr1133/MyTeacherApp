<?php

namespace App\Http\Responders\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegisterResponder
{
    /**
     * アカウント登録画面を表示
     *
     * @return View
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * 登録成功時のリダイレクト
     *
     * @param string $route
     * @param string $message
     * @return RedirectResponse
     */
    public function successRedirect(string $route = 'avatars.create', string $message = 'アカウントを作成しました。教師アバターを作成しましょう！'): RedirectResponse
    {
        return redirect()
            ->route($route)
            ->with('success', $message);
    }

    /**
     * 登録失敗時のリダイレクト
     *
     * @param string $message
     * @return RedirectResponse
     */
    public function errorRedirect(string $message = 'アカウントの作成に失敗しました。'): RedirectResponse
    {
        return redirect()
            ->back()
            ->withInput()
            ->with('error', $message);
    }
}
