<?php

namespace App\Http\Actions\Api\Legal;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 本人同意状態取得APIアクション
 * 
 * モバイルアプリから13歳到達時の本人同意状態を取得します。
 * Phase 6D: 13歳到達時の本人再同意
 */
class GetSelfConsentStatusApiAction
{
    /**
     * 本人同意状態を取得
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => '認証が必要です。',
            ], 401);
        }

        return response()->json([
            'requires_self_consent' => $user->needsSelfConsent(),
            'age' => $user->birthdate?->age,
            'is_minor' => $user->is_minor,
            'created_by_user_id' => $user->created_by_user_id,
            'consent_given_by_user_id' => $user->consent_given_by_user_id,
            'self_consented_at' => $user->self_consented_at?->toIso8601String(),
            'privacy_policy' => [
                'current_version' => $user->privacy_policy_version,
                'agreed_at' => $user->privacy_policy_agreed_at?->toIso8601String(),
            ],
            'terms' => [
                'current_version' => $user->terms_version,
                'agreed_at' => $user->terms_agreed_at?->toIso8601String(),
            ],
        ]);
    }
}
