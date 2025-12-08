<?php

namespace App\Http\Actions\Api\Subscription;

use App\Http\Responders\Api\Subscription\SubscriptionApiResponder;
use App\Services\Subscription\SubscriptionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * サブスクリプションキャンセルAction（モバイルAPI用）
 * 
 * エンドポイント: POST /api/subscriptions/cancel
 */
class CancelSubscriptionApiAction
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
     * サブスクリプションをキャンセル
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

            // キャンセル実行（期間終了時に解約）
            $this->subscriptionService->cancelSubscription($group);

            return $this->responder->cancelSuccessResponse();
        } catch (\Exception $e) {
            return $this->responder->errorResponse($e->getMessage(), 500);
        }
    }
}
