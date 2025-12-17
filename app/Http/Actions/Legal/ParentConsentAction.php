<?php

namespace App\Http\Actions\Legal;

use App\Models\User;
use App\Http\Responders\Legal\ParentConsentResponder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * 保護者同意Action（Web）
 * 
 * 13歳未満ユーザーの保護者が同意リンクをクリックした際の処理。
 * トークン検証、同意記録、アカウント有効化を行います。
 * 
 * Phase 5-2: 13歳未満新規登録時の保護者メール同意実装
 */
class ParentConsentAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        private ParentConsentResponder $responder
    ) {}

    /**
     * 保護者同意画面を表示
     * 
     * @param string $token 保護者同意確認用トークン
     * @return View|RedirectResponse
     */
    public function show(string $token): View|RedirectResponse
    {
        Log::info('[ParentConsent] Show consent form requested', [
            'token' => substr($token, 0, 20) . '...',
        ]);

        // トークンでユーザーを検索
        $user = User::where('parent_consent_token', $token)
            ->where('is_minor', true)
            ->whereNull('parent_consented_at')
            ->first();

        // トークンが無効または既に同意済み
        if (!$user) {
            Log::warning('[ParentConsent] Invalid or already used token', [
                'token' => substr($token, 0, 20) . '...',
            ]);
            return $this->responder->invalidToken();
        }

        // 期限切れチェック
        if ($user->isParentConsentExpired()) {
            Log::warning('[ParentConsent] Token expired', [
                'user_id' => $user->id,
                'expired_at' => $user->parent_consent_expires_at,
            ]);
            
            return $this->responder->expired($user);
        }

        // 同意画面を表示
        return $this->responder->showConsentForm($user, $token);
    }

    /**
     * 保護者同意を記録
     * 
     * @param string $token 保護者同意確認用トークン
     * @return RedirectResponse
     */
    public function store(string $token): RedirectResponse
    {
        Log::info('[ParentConsent] Consent submission started', [
            'token' => substr($token, 0, 20) . '...',
        ]);

        try {
            // トークンでユーザーを検索
            $user = User::where('parent_consent_token', $token)
                ->where('is_minor', true)
                ->whereNull('parent_consented_at')
                ->first();

            // トークンが無効または既に同意済み
            if (!$user) {
                Log::warning('[ParentConsent] Invalid or already used token on consent', [
                    'token' => substr($token, 0, 20) . '...',
                ]);
                return $this->responder->invalidToken();
            }

            // 期限切れチェック
            if ($user->isParentConsentExpired()) {
                Log::warning('[ParentConsent] Token expired on consent', [
                    'user_id' => $user->id,
                    'expired_at' => $user->parent_consent_expires_at,
                ]);
                
                return $this->responder->expired($user);
            }

            // 同意記録をトランザクション内で実行
            DB::transaction(function () use ($user) {
                $user->update([
                    'parent_consented_at' => now(),
                    'parent_consent_token' => null, // トークン無効化（再利用防止）
                    // プライバシーポリシー・利用規約の同意記録
                    'privacy_policy_version' => config('legal.current_versions.privacy_policy'),
                    'terms_version' => config('legal.current_versions.terms_of_service'),
                    'privacy_policy_agreed_at' => now(),
                    'terms_agreed_at' => now(),
                    'consent_given_by_user_id' => null, // 保護者による同意（ユーザーIDなし）
                ]);
            });

            Log::info('[ParentConsent] Parent consented', [
                'user_id' => $user->id,
                'username' => $user->username,
                'parent_email' => $user->parent_email,
                'consented_at' => now(),
            ]);

            return $this->responder->success($user);

        } catch (\Exception $e) {
            Log::error('[ParentConsent] Consent failed', [
                'token' => $token,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('同意の記録中にエラーが発生しました。もう一度お試しください。');
        }
    }
}
