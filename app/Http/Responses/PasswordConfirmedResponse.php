<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\PasswordConfirmedResponse as PasswordConfirmedResponseContract;

/**
 * カスタムパスワード確認レスポンス
 * 
 * Fortifyのデフォルト実装では、intended URLがPOSTリクエストの場合に
 * 正しくリダイレクトできない問題があるため、カスタマイズしています。
 */
class PasswordConfirmedResponse implements PasswordConfirmedResponseContract
{
    /**
     * パスワード確認成功時のレスポンスを作成
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 201);
        }

        // intended URLを取得（セッションに保存されている元のURL）
        $intendedUrl = $request->session()->get('url.intended');

        // 2FA関連のURLの場合は、プロフィール編集ページにリダイレクト
        // （再度POSTリクエストを送信することはできないため）
        if ($intendedUrl && str_contains($intendedUrl, '/user/two-factor-authentication')) {
            // セッションにパスワード確認済みのフラグがあることを確認
            // （これにより、次回の2FA有効化リクエストはミドルウェアを通過する）
            return redirect()->route('profile.edit')
                ->with('status', 'password-confirmed')
                ->with('message', 'パスワードが確認されました。再度操作を実行してください。');
        }

        // その他の場合は、元のURLまたはダッシュボードにリダイレクト
        return redirect()->intended(route('dashboard'));
    }
}
