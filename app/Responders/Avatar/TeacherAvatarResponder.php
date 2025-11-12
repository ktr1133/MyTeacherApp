<?php

namespace App\Responders\Avatar;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TeacherAvatarResponder
{
    /**
     * 教師アバター作成画面を表示
     */
    public function create(): View
    {
        return view('avatars.create');
    }

    /**
     * 教師アバター編集画面を表示
     *
     * @param array $data
     * @return View
     */
    public function edit(array $data): View
    {
        return view('avatars.edit', $data);
    }

    /**
     * ダッシュボードへリダイレクト
     *
     * @param string $message
     * @return RedirectResponse
     */
    public function redirectToDashboard(string $message = 'アバターを作成しました'): RedirectResponse
    {
        return redirect()
            ->route('dashboard')
            ->with('success', $message);
    }

    /**
     * アバター編集画面へリダイレクト
     *
     * @param string $message
     * @return RedirectResponse
     */
    public function redirectToEdit(string $message): RedirectResponse
    {
        return redirect()
            ->route('avatars.edit')
            ->with('success', $message);
    }
}