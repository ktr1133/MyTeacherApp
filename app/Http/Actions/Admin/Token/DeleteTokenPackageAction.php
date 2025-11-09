<?php

namespace App\Http\Actions\Admin\Token;

use App\Services\Token\TokenPackageServiceInterface;
use Illuminate\Http\Request;

/**
 * トークンパッケージ削除アクション（管理者用）
 */
class DeleteTokenPackageAction
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

        $this->service->delete($package);

        return redirect()
            ->route('admin.token-packages')
            ->with('success', 'パッケージを削除しました');
    }
}