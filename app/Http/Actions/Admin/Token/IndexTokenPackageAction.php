<?php

namespace App\Http\Actions\Admin\Token;

use App\Services\Token\TokenPackageServiceInterface;
use App\Responders\Admin\TokenPackageResponder;

/**
 * トークンパッケージ一覧表示アクション（管理者用）
 */
class IndexTokenPackageAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        private TokenPackageServiceInterface $service,
        private TokenPackageResponder $responder
    ) {}

    public function __invoke()
    {
        $packages = $this->service->list(20);
        $freeTokenAmount = $this->service->getFreeTokenAmount();
        
        return $this->responder->index([
            'packages' => $packages,
            'freeTokenAmount' => $freeTokenAmount,
        ]);
    }
}