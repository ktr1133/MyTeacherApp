<?php

namespace App\Http\Actions\Api\Subscription;

use App\Http\Responders\Api\Subscription\SubscriptionApiResponder;
use App\Services\Subscription\SubscriptionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * サブスクリプションプラン変更Action
 * 
 * エンドポイント: POST /api/subscriptions/update
 */
class UpdateSubscriptionPlanAction
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
     * サブスクリプションプランを変更
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

            // バリデーション
            $validator = Validator::make($request->all(), [
                'new_plan' => 'required|string|in:family,enterprise',
                'additional_members' => 'integer|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return $this->responder->errorResponse(
                    $validator->errors()->first(),
                    400
                );
            }

            $newPlan = $request->input('new_plan');
            $additionalMembers = $request->input('additional_members', 0);

            // プラン変更
            $this->subscriptionService->updateSubscriptionPlan(
                $group,
                $newPlan,
                $additionalMembers
            );

            return $this->responder->updateSuccessResponse();
        } catch (\Exception $e) {
            return $this->responder->errorResponse($e->getMessage(), 500);
        }
    }
}
