<?php

namespace App\Http\Actions\Api\Legal;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 保護者同意API Action（モバイル）
 * 
 * 13歳未満ユーザーの保護者が同意リンクをクリックした際のAPI処理。
 * トークン検証、同意記録、アカウント有効化を行います。
 * 
 * Phase 5-2: 13歳未満新規登録時の保護者メール同意実装
 */
class ParentConsentApiAction
{
    /**
     * 保護者同意トークンの検証
     * 
     * @param string $token 保護者同意確認用トークン
     * @return JsonResponse
     */
    public function show(string $token): JsonResponse
    {
        // トークンでユーザーを検索
        $user = User::where('parent_consent_token', $token)
            ->where('is_minor', true)
            ->whereNull('parent_consented_at')
            ->first();

        // トークンが無効または既に同意済み
        if (!$user) {
            return response()->json([
                'message' => 'トークンが無効です。既に同意済みか、トークンの有効期限が切れています。',
                'valid' => false,
            ], 404);
        }

        // 期限切れチェック
        if ($user->isParentConsentExpired()) {
            Log::warning('[ParentConsentApi] Token expired', [
                'user_id' => $user->id,
                'expired_at' => $user->parent_consent_expires_at,
            ]);
            
            return response()->json([
                'message' => '同意期限が切れています。アカウントは削除される予定です。',
                'valid' => false,
                'expired' => true,
                'expired_at' => $user->parent_consent_expires_at?->toISOString(),
            ], 410); // 410 Gone
        }

        // トークン有効
        return response()->json([
            'valid' => true,
            'child_user' => [
                'username' => $user->username,
                'email' => $user->email,
                'age' => $user->birthdate?->age,
                'created_at' => $user->created_at->toISOString(),
            ],
            'expires_at' => $user->parent_consent_expires_at?->toISOString(),
            'privacy_policy_url' => route('privacy-policy'),
            'terms_url' => route('terms-of-service'),
        ]);
    }

    /**
     * 保護者同意を記録
     * 
     * @param Request $request
     * @param string $token 保護者同意確認用トークン
     * @return JsonResponse
     */
    public function store(Request $request, string $token): JsonResponse
    {
        try {
            // トークンでユーザーを検索
            $user = User::where('parent_consent_token', $token)
                ->where('is_minor', true)
                ->whereNull('parent_consented_at')
                ->first();

            // トークンが無効または既に同意済み
            if (!$user) {
                return response()->json([
                    'message' => 'トークンが無効です。既に同意済みか、トークンの有効期限が切れています。',
                ], 404);
            }

            // 期限切れチェック
            if ($user->isParentConsentExpired()) {
                Log::warning('[ParentConsentApi] Token expired on consent', [
                    'user_id' => $user->id,
                    'expired_at' => $user->parent_consent_expires_at,
                ]);
                
                return response()->json([
                    'message' => '同意期限が切れています。アカウントは削除される予定です。',
                    'expired' => true,
                ], 410);
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

            Log::info('[ParentConsentApi] Parent consented', [
                'user_id' => $user->id,
                'username' => $user->username,
                'parent_email' => $user->parent_email,
                'consented_at' => now(),
            ]);

            return response()->json([
                'message' => '保護者同意が記録されました。お子様がログインできるようになりました。',
                'success' => true,
                'user' => [
                    'username' => $user->username,
                    'email' => $user->email,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('[ParentConsentApi] Consent failed', [
                'token' => $token,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => '同意の記録中にエラーが発生しました。もう一度お試しください。',
            ], 500);
        }
    }
}
