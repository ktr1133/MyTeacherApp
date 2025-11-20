<?php

namespace App\Responders\Avatar;

use App\Models\TeacherAvatar;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TeacherAvatarResponder
{
    /**
     * アバター作成画面を表示
     */
    public function create(): View
    {
        // ビュー共有変数（ミドルウェアでセット済み）を利用
        $isChildTheme = view()->shared('isChildTheme', false);
        
        // 子ども用テーマの場合
        if ($isChildTheme) {
            return view('avatars.create-child');
        }
        
        // 大人用テーマの場合
        return view('avatars.create');
    }

    /**
     * アバター編集画面を表示
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