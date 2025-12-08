<?php

namespace App\Http\Actions\Api\Subscription;

use App\Http\Responders\Api\Subscription\SubscriptionApiResponder;
use App\Services\Subscription\SubscriptionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Stripe Checkout Session作成Action（モバイルAPI用）
 * 
 * エンドポイント: POST /api/subscriptions/checkout
 */
class CreateCheckoutSessionApiAction
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
     * Checkout Sessionを作成
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

            // バリデーション
            $validator = Validator::make($request->all(), [
                'plan' => 'required|string|in:family,enterprise',
                'additional_members' => 'integer|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return $this->responder->errorResponse(
                    $validator->errors()->first(),
                    400
                );
            }

            $plan = $request->input('plan');
            $additionalMembers = $request->input('additional_members', 0);

            // Checkout Session作成
            $checkout = $this->subscriptionService->createCheckoutSession(
                $group,
                $plan,
                $additionalMembers
            );

            return $this->responder->checkoutSessionResponse($checkout->url);
        } catch (\Exception $e) {
            return $this->responder->errorResponse($e->getMessage(), 500);
        }
    }
}
