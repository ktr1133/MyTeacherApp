<?php

namespace App\Http\Actions\Api\Legal;

use App\Http\Requests\Api\Legal\ReconsentApiRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * 再同意API Action
 * 
 * モバイルアプリからの再同意処理を行います。
 * Phase 6C: 再同意プロセス実装
 */
class ReconsentApiAction
{
    /**
     * 再同意を処理する
     * 
     * @param \App\Http\Requests\Api\Legal\ReconsentApiRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(ReconsentApiRequest $request): JsonResponse
    {
        $user = $request->user();
        
        // 同意データを取得
        $validated = $request->validated();
        
        try {
            // プライバシーポリシーへの同意を記録
            if ($validated['privacy_policy_consent']) {
                $user->recordLegalConsent(
                    'privacy_policy',
                    config('legal.current_versions.privacy_policy')
                );
            }
            
            // 利用規約への同意を記録
            if ($validated['terms_consent']) {
                $user->recordLegalConsent(
                    'terms',
                    config('legal.current_versions.terms_of_service')
                );
            }
            
            // 最新のユーザー情報を再取得
            $user->refresh();
            
            Log::info('Legal reconsent completed (API)', [
                'user_id' => $user->id,
                'privacy_policy_version' => $user->privacy_policy_version,
                'terms_version' => $user->terms_version,
            ]);
            
            return response()->json([
                'message' => '同意が完了しました。',
                'user' => [
                    'privacy_policy_version' => $user->privacy_policy_version,
                    'terms_version' => $user->terms_version,
                    'privacy_policy_agreed_at' => $user->privacy_policy_agreed_at?->toIso8601String(),
                    'terms_agreed_at' => $user->terms_agreed_at?->toIso8601String(),
                ],
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Legal reconsent failed (API)', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => '同意の記録に失敗しました。もう一度お試しください。',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
