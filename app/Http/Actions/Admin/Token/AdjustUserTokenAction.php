<?php

namespace App\Http\Actions\Admin\Token;

use App\Http\Requests\Token\AdjustTokenRequest;
use App\Services\Token\TokenServiceInterface;
use Illuminate\Http\RedirectResponse;

/**
 * トークン調整アクション（管理者用）
 */
class AdjustUserTokenAction
{
    public function __construct(
        private TokenServiceInterface $tokenService
    ) {}

    /**
     * トークンを調整する
     *
     * @param AdjustTokenRequest $request
     * @return RedirectResponse
     */
    public function __invoke(AdjustTokenRequest $request): RedirectResponse
    {
        $admin = $request->user();

        $success = $this->tokenService->adjustTokensByAdmin(
            $request->tokenable_id,
            $request->tokenable_type,
            $request->amount,
            $admin,
            $request->note
        );

        if ($success) {
            return redirect()
                ->back()
                ->with('success', 'トークンを調整しました。');
        }

        return redirect()
            ->back()
            ->with('error', 'トークンの調整に失敗しました。');
    }
}