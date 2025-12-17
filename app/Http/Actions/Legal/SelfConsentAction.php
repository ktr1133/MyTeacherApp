<?php

namespace App\Http\Actions\Legal;

use App\Http\Requests\Legal\SelfConsentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * 本人同意アクション
 * 
 * 13歳到達時の本人同意を処理します。
 * Phase 6D: 13歳到達時の本人再同意
 */
class SelfConsentAction
{
    /**
     * 本人同意を処理
     * 
     * @param SelfConsentRequest $request
     * @return RedirectResponse
     */
    public function __invoke(SelfConsentRequest $request): RedirectResponse
    {
        $user = $request->user();

        try {
            // プライバシーポリシーへの同意を記録
            $user->recordLegalConsent('privacy_policy');
            
            // 利用規約への同意を記録
            $user->recordLegalConsent('terms_of_service');
            
            // 本人同意日時を記録
            $user->self_consented_at = now();
            
            // 同意者を本人に変更（親→本人）
            $user->consent_given_by_user_id = $user->id;
            
            $user->save();

            Log::info('Self consent recorded (13th birthday)', [
                'user_id' => $user->id,
                'age' => $user->birthdate?->age,
                'previous_consent_giver' => $user->created_by_user_id,
                'self_consented_at' => $user->self_consented_at->toDateTimeString(),
            ]);

            return redirect()->route('dashboard')
                ->with('success', 'おめでとうございます！本人同意が完了しました。これからはあなた自身でサービスを利用できます。');

        } catch (\Exception $e) {
            Log::error('Self consent failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withErrors(['error' => '本人同意の記録に失敗しました。もう一度お試しください。'])
                ->withInput();
        }
    }
}
