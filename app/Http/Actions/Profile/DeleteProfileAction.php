<?php

namespace App\Http\Actions\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteProfileAction
{
    /**
     * プロフィール（ユーザーアカウント）削除
     * - パスワード確認（current_password ルール）
     * - アバターがあれば削除（storage/app/public/avatars）
     * - ユーザー削除（SoftDeletes が有効なら論理削除）
     * - ログアウトしてセッション無効化
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        // パスワード確認
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        DB::transaction(function () use ($user) {
            // 既存のアバターを削除（列名は avatar_path 想定）
            if (!empty($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            // 必要に応じて関連データの削除/detach をここに追加

            // ユーザー削除（SoftDeletes がある場合は論理削除）
            $user->delete();
        });

        // セッション終了
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'アカウントを削除しました。');
    }
}