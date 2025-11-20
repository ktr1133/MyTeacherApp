<?php

namespace App\Http\Actions\Admin\Token;

use App\Responders\Admin\TokenPackageResponder;

/**
 * トークンパッケージ新規作成表示アクション（管理者用）
 */
class CreateTokenPackageAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        private TokenPackageResponder $responder
    ) {}

    public function __invoke()
    {
        return $this->responder->create();
    }
}