<?php

namespace App\Http\Actions\Subscription;

use App\Helpers\AuthHelper;
use App\Http\Responders\Subscription\SubscriptionResponder;
use App\Services\Subscription\SubscriptionServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * サブスクリプション管理画面表示アクション
 */
class ManageSubscriptionAction
{
    /**
     * @param SubscriptionServiceInterface $subscriptionService サブスクリプションサービス
     * @param SubscriptionResponder $responder レスポンダー
     */
    public function __construct(
        protected SubscriptionServiceInterface $subscriptionService,
        protected SubscriptionResponder $responder
    ) {}

    /**
     * サブスクリプション管理画面を表示（統合画面にリダイレクト）
     * 
     * @param Request $request HTTPリクエスト
     * @return RedirectResponse
     */
    public function __invoke(Request $request): RedirectResponse
    {
        // 統合されたサブスクリプション画面にリダイレクト
        return redirect()->route('subscriptions.index');
    }
}
