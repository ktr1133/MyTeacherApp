<?php

namespace App\Http\Responders\Legal;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * 保護者同意Responder
 * 
 * Phase 5-2: 13歳未満新規登録時の保護者メール同意実装
 */
class ParentConsentResponder
{
    /**
     * 保護者同意画面を表示
     * 
     * @param User $user 子ユーザー
     * @param string $token トークン
     * @return View
     */
    public function showConsentForm(User $user, string $token): View
    {
        return view('legal.parent-consent', [
            'user' => $user,
            'token' => $token,
            'expiresAt' => $user->parent_consent_expires_at,
            'privacyPolicyUrl' => route('privacy-policy'),
            'termsUrl' => route('terms-of-service'),
        ]);
    }

    /**
     * 同意成功レスポンス
     * 
     * Phase 5-2拡張: 招待リンク付き完了画面にリダイレクト
     * 
     * @param User $user 子ユーザー
     * @return RedirectResponse
     */
    public function success(User $user): RedirectResponse
    {
        // デバッグログ追加
        Log::info('[ParentConsentResponder] Redirecting to completion page', [
            'user_id' => $user->id,
            'username' => $user->username,
            'parent_invitation_token' => $user->parent_invitation_token,
            'parent_invitation_token_exists' => !empty($user->parent_invitation_token),
        ]);

        // 招待トークンが存在しない場合のエラーハンドリング
        if (empty($user->parent_invitation_token)) {
            Log::error('[ParentConsentResponder] Parent invitation token is missing', [
                'user_id' => $user->id,
                'username' => $user->username,
            ]);
            
            return redirect()->route('login')
                ->with('warning', 'アカウントが有効になりました。お子様はログインできますが、保護者アカウントの招待リンクを取得できませんでした。');
        }

        return redirect()
            ->route('legal.parent-consent-complete', ['token' => $user->parent_invitation_token])
            ->with('child_user', $user);
    }

    /**
     * トークン無効エラー
     * 
     * @return RedirectResponse
     */
    public function invalidToken(): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['token' => 'トークンが無効です。既に同意済みか、トークンの有効期限が切れています。']);
    }

    /**
     * 期限切れエラー
     * 
     * @param User $user 子ユーザー
     * @return RedirectResponse
     */
    public function expired(User $user): RedirectResponse
    {
        return redirect()->route('login')
            ->withErrors([
                'expired' => '同意期限が切れています（期限: ' . $user->parent_consent_expires_at?->format('Y年m月d日 H:i') . '）。' .
                            'アカウントは自動削除される予定です。再度登録が必要な場合は、新規登録画面からお手続きください。'
            ]);
    }

    /**
     * 一般エラー
     * 
     * @param string $message エラーメッセージ
     * @return RedirectResponse
     */
    public function error(string $message): RedirectResponse
    {
        return redirect()->back()
            ->withErrors(['error' => $message])
            ->withInput();
    }
}
