<?php

namespace App\Http\Actions\Api\Subscription;

use App\Http\Responders\Api\Subscription\SubscriptionApiResponder;
use App\Services\Subscription\SubscriptionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 請求履歴取得Action
 * 
 * エンドポイント: GET /api/subscriptions/invoices
 */
class GetInvoicesAction
{
    /**
     * @param SubscriptionServiceInterface $subscriptionService サブスクリプションサービス
     * @param SubscriptionApiResponder $responder レスポンダー
     */
    public function __construct(
        protected SubscriptionServiceInterface $subscriptionService,
        protected SubscriptionApiResponder $responder
    ) {}

    /**
     * 請求履歴を取得
     * 
     * @param Request $request リクエスト
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $group = $user->group;

            // 子どもテーマユーザーはアクセス拒否
            if ($user->useChildTheme()) {
                return $this->responder->forbiddenResponse();
            }

            // サブスクリプション加入チェック
            if (!$this->subscriptionService->isGroupSubscribed($group)) {
                return $this->responder->noSubscriptionResponse();
            }

            // 請求履歴取得
            $limit = $request->input('limit', 10);
            $invoices = $this->subscriptionService->getInvoiceHistory($group, $limit);

            return $this->responder->invoicesResponse($invoices);
        } catch (\Exception $e) {
            return $this->responder->errorResponse($e->getMessage(), 500);
        }
    }
}
