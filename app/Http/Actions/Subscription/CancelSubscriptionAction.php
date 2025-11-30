<?php

namespace App\Http\Actions\Subscription;

use App\Http\Responders\Subscription\SubscriptionResponder;
use App\Services\Subscription\SubscriptionServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * サブスクリプションキャンセルアクション
 */
class CancelSubscriptionAction
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
     * サブスクリプションをキャンセル（期間終了時に解約）
     * 
     * @param Request $request HTTPリクエスト
     * @return RedirectResponse
     */
    public function __invoke(Request $request): RedirectResponse
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
            $immediately = $request->boolean('immediately', false);

            if ($immediately) {
                $this->subscriptionService->cancelSubscriptionNow($group);
                $message = 'サブスクリプションを即座にキャンセルしました。';
            } else {
                $this->subscriptionService->cancelSubscription($group);
                $message = 'サブスクリプションをキャンセルしました。有効期限まで引き続きご利用いただけます。';
            }

            return $this->responder->success($message);
        } catch (\RuntimeException $e) {
            Log::error('Subscription cancellation failed', [
                'group_id' => $group->id,
                'immediately' => $immediately ?? false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('サブスクリプションのキャンセルに失敗しました。時間をおいて再度お試しください。');
        }
    }
}
