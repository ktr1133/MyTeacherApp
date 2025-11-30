<?php

namespace App\Http\Actions\Subscription;

use App\Services\Subscription\SubscriptionServiceInterface;
use App\Http\Responders\Subscription\SubscriptionResponder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ShowSubscriptionPlansAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected SubscriptionServiceInterface $subscriptionService,
        protected SubscriptionResponder $responder
    ) {}

    /**
     * サブスクリプションプラン選択画面を表示
     * 
     * @param Request $request
     * @return View|Response
     */
    public function __invoke(Request $request): View|Response
    {
        $user = $request->user();
        $group = $user->group;

        // グループが存在しない場合
        if (!$group) {
            return response()->view('errors.no-group', [
                'isChildTheme' => $user->useChildTheme(),
                'message' => 'サブスクリプションを管理するには、まずグループを作成してください。',
            ], 403);
        }

        // 子どもテーマの場合は非表示
        if ($user->useChildTheme()) {
            abort(404);
        }

        // グループ管理権限チェック
        if (!$this->subscriptionService->canManageSubscription($group)) {
            abort(403, 'サブスクリプション管理の権限がありません。');
        }

        // プラン情報と現在のサブスクリプション取得
        $plans = $this->subscriptionService->getAvailablePlans();
        $currentSubscription = $this->subscriptionService->getCurrentSubscription($group);
        
        // 請求履歴取得（サブスクリプション加入者のみ）
        $invoices = $currentSubscription 
            ? $this->subscriptionService->getInvoiceHistory($group, 10)
            : [];

        return $this->responder->showPlans(
            $plans,
            $currentSubscription,
            $user->useChildTheme(),
            $invoices
        );
    }
}
