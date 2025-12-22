<?php

namespace App\Http\Actions\Profile\Group;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Profile\GroupServiceInterface;
use App\Services\Subscription\SubscriptionServiceInterface;
use App\Responders\Profile\Group\GroupResponder;

class EditGroupAction
{
    public function __construct(
        private GroupServiceInterface $service,
        private SubscriptionServiceInterface $subscriptionService,
        private GroupResponder $responder
    ) {}

    public function __invoke(Request $request)
    {
        $user = Auth::user();
        [$group, $members] = $this->service->getEditData($user);

        // サブスクリプション判定（グループタスク自動作成機能用）
        $hasSubscription = $group ? $this->subscriptionService->isGroupSubscribed($group) : false;

        return $this->responder->viewEdit($group, $members, $hasSubscription);
    }
}