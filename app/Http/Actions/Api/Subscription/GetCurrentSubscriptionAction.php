<?php

namespace App\Http\Actions\Api\Subscription;

use App\Http\Responders\Api\Subscription\SubscriptionApiResponder;
use App\Services\Subscription\SubscriptionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 現在のサブスクリプション情報取得Action
 * 
 * エンドポイント: GET /api/subscriptions/current
 */
class GetCurrentSubscriptionAction
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
     * 現在のサブスクリプション情報を取得
     * 
     * @param Request $request リクエスト
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $group = $user->group;

            // グループが存在しない場合はnullを返す
            if (!$group) {
                return $this->responder->currentSubscriptionResponse(null);
            }

            // サブスクリプション情報の取得は子どもテーマでも許可
            // （表示のみ - プラン変更やキャンセルは親ユーザーのみ）

            // 現在のサブスクリプション取得
            $subscription = $this->subscriptionService->getCurrentSubscription($group);

            return $this->responder->currentSubscriptionResponse($subscription);
        } catch (\Exception $e) {
            return $this->responder->errorResponse($e->getMessage(), 500);
        }
    }
}
