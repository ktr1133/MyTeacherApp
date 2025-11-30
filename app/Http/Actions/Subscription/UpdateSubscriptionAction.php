<?php

namespace App\Http\Actions\Subscription;

use App\Http\Requests\Subscription\UpdateSubscriptionRequest;
use App\Http\Responders\Subscription\SubscriptionResponder;
use App\Services\Subscription\SubscriptionServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * サブスクリプションプラン変更アクション
 */
class UpdateSubscriptionAction
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
     * サブスクリプションプランを変更
     * 
     * @param UpdateSubscriptionRequest $request HTTPリクエスト
     * @return RedirectResponse
     */
    public function __invoke(UpdateSubscriptionRequest $request): RedirectResponse
    {
        $user = $request->user();
        $group = $user->group;

        // グループが存在しない場合
        if (!$group) {
            return $this->responder->error('グループが存在しません。');
        }

        // 管理権限チェック
        if (!$this->subscriptionService->canManageSubscription($group)) {
            abort(403, 'サブスクリプションの管理権限がありません。');
        }

        try {
            $data = $request->validated();
            
            $this->subscriptionService->updateSubscriptionPlan(
                $group,
                $data['plan'],
                $data['additional_members'] ?? 0
            );

            return $this->responder->success('プランを変更しました。');
        } catch (\RuntimeException $e) {
            Log::error('Subscription update failed', [
                'group_id' => $group->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('プランの変更に失敗しました。時間をおいて再度お試しください。');
        }
    }
}
