<?php

namespace App\Http\Actions\Legal;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 再同意画面表示Action
 * 
 * Phase 6C: 再同意プロセス実装
 */
class ShowReconsentAction
{
    /**
     * 再同意画面を表示する
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        
        return view('legal.reconsent', [
            'user' => $user,
            'currentPrivacyVersion' => $user->privacy_policy_version,
            'currentTermsVersion' => $user->terms_version,
            'requiredPrivacyVersion' => config('legal.current_versions.privacy_policy'),
            'requiredTermsVersion' => config('legal.current_versions.terms_of_service'),
        ]);
    }
}
