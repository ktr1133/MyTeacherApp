<?php

namespace App\Http\Actions\Api\Legal;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * 同意状態確認API Action
 * 
 * ユーザーが最新の法的文書に同意しているか確認します。
 * Phase 6C: 再同意プロセス実装
 */
class GetConsentStatusApiAction
{
    /**
     * 同意状態を取得する
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => '認証が必要です。',
            ], 401);
        }
        
        $needsPrivacyReconsent = $user->needsPrivacyPolicyReconsent();
        $needsTermsReconsent = $user->needsTermsReconsent();
        $needsAnyReconsent = $user->needsAnyLegalReconsent();
        
        return response()->json([
            'requires_reconsent' => $needsAnyReconsent,
            'privacy_policy' => [
                'current_version' => $user->privacy_policy_version,
                'required_version' => config('legal.current_versions.privacy_policy'),
                'needs_reconsent' => $needsPrivacyReconsent,
                'agreed_at' => $user->privacy_policy_agreed_at?->toIso8601String(),
            ],
            'terms' => [
                'current_version' => $user->terms_version,
                'required_version' => config('legal.current_versions.terms_of_service'),
                'needs_reconsent' => $needsTermsReconsent,
                'agreed_at' => $user->terms_agreed_at?->toIso8601String(),
            ],
        ]);
    }
}
