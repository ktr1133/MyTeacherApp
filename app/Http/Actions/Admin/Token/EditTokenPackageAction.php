<?php

namespace App\Http\Actions\Admin\Token;

use App\Services\Token\TokenPackageServiceInterface;
use App\Responders\Admin\TokenPackageResponder;
use Illuminate\Http\Request;

/**
 * トークンパッケージ編集表示アクション（管理者用）
 */
class EditTokenPackageAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        private TokenPackageServiceInterface $service,
        private TokenPackageResponder $responder
    ) {}

    public function __invoke(Request $request, $packageId)
    {
        $package = $this->service->find($packageId);
        abort_unless($package, 404);

        return $this->responder->edit(['package' => $package]);
    }
}