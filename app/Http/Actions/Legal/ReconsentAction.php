<?php

namespace App\Http\Actions\Legal;

use App\Http\Requests\Legal\ReconsentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * プライバシーポリシー・利用規約 再同意Action
 * 
 * ユーザーが最新版の法的文書に再同意する処理を行います。
 * Phase 6C: 再同意プロセス実装
 */
class ReconsentAction
{
    /**
     * 再同意を処理する
     * 
     * @param \App\Http\Requests\Legal\ReconsentRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(ReconsentRequest $request): RedirectResponse
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
            
            Log::info('Legal reconsent completed', [
                'user_id' => $user->id,
                'privacy_policy_version' => $user->privacy_policy_version,
                'terms_version' => $user->terms_version,
            ]);
            
            return redirect()
                ->route('dashboard')
                ->with('success', '最新のプライバシーポリシー・利用規約への同意が完了しました。');
                
        } catch (\Exception $e) {
            Log::error('Legal reconsent failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()
                ->back()
                ->withErrors(['error' => '同意の記録に失敗しました。もう一度お試しください。'])
                ->withInput();
        }
    }
}
