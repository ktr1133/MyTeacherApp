<?php

namespace App\Http\Actions\Admin\Token;

use Illuminate\Http\Request;
use App\Services\Token\TokenPackageServiceInterface;

class UpdateFreeTokenAmountAction
{
    public function __construct(
        private TokenPackageServiceInterface $tokenPackageService,
    ) {}

    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'free_token_amount' => 'required|integer|min:0',
        ]);

        $this->tokenPackageService->updateFreeTokenAmount($validated['free_token_amount']);

        return redirect()
            ->route('admin.token-packages')
            ->with('success', '無料トークン数を更新しました');
    }
}