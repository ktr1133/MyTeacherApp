<?php

namespace App\Http\Actions\Api\Legal;

use App\Http\Requests\Api\Legal\SelfConsentApiRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * 本人同意送信APIアクション
 * 
 * モバイルアプリから13歳到達時の本人同意を受け取ります。
 * Phase 6D: 13歳到達時の本人再同意
 */
class SelfConsentApiAction
{
    /**
     * 本人同意を処理
     * 
     * @param SelfConsentApiRequest $request
     * @return JsonResponse
     */
    public function __invoke(SelfConsentApiRequest $request): JsonResponse
    {
        $user = $request->user();

        try {
            // プライバシーポリシーへの同意を記録
            if ($request->input('privacy_policy_consent')) {
                $user->recordLegalConsent('privacy_policy');
            }
            
            // 利用規約への同意を記録
            if ($request->input('terms_consent')) {
                $user->recordLegalConsent('terms_of_service');
            }
            
            // 本人同意日時を記録
            $user->self_consented_at = now();
            
            // 同意者を本人に変更（親→本人）
            $user->consent_given_by_user_id = $user->id;
            
            $user->save();

            Log::info('Self consent recorded via API (13th birthday)', [
                'user_id' => $user->id,
                'age' => $user->birthdate?->age,
                'previous_consent_giver' => $user->created_by_user_id,
                'self_consented_at' => $user->self_consented_at->toDateTimeString(),
            ]);

            return response()->json([
                'message' => 'おめでとうございます！本人同意が完了しました。',
                'user' => [
                    'id' => $user->id,
                    'age' => $user->birthdate?->age,
                    'privacy_policy_version' => $user->privacy_policy_version,
                    'terms_version' => $user->terms_version,
                    'privacy_policy_agreed_at' => $user->privacy_policy_agreed_at?->toIso8601String(),
                    'terms_agreed_at' => $user->terms_agreed_at?->toIso8601String(),
                    'self_consented_at' => $user->self_consented_at?->toIso8601String(),
                    'consent_given_by_user_id' => $user->consent_given_by_user_id,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Self consent failed via API', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Self consent failed',
                'message' => '本人同意の記録に失敗しました。もう一度お試しください。',
            ], 500);
        }
    }
}
