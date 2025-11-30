<?php

namespace App\Http\Actions\Subscription;

use App\Http\Responders\Subscription\SubscriptionResponder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionSuccessAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected SubscriptionResponder $responder
    ) {}

    /**
     * サブスクリプション処理成功画面を表示
     * 
     * @param Request $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        
        return $this->responder->showSuccess($user->useChildTheme());
    }
}
