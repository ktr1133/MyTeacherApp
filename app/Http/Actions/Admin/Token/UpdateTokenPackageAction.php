<?php

namespace App\Http\Actions\Admin\Token;

use App\Services\Token\TokenPackageServiceInterface;
use Illuminate\Http\Request;

/**
 * トークンパッケージ更新アクション（管理者用）
 */
class UpdateTokenPackageAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        private TokenPackageServiceInterface $service
    ) {}

    public function __invoke(Request $request, $packageId)
    {
        $package = $this->service->find($packageId);
        abort_unless($package, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'token_amount' => 'required|integer|min:1',
            'price' => 'required|integer|min:0',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $this->service->update($package, $validated);

        return redirect()
            ->route('admin.token-packages')
            ->with('success', 'パッケージを更新しました');
    }
}