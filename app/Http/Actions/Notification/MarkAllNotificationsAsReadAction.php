<?php

namespace App\Http\Actions\Notification;

use App\Services\Notification\NotificationServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

/**
 * 全通知既読化アクション
 */
class MarkAllNotificationsAsReadAction
{
    public function __construct(
        private NotificationServiceInterface $notificationService
    ) {}

    /**
     * 全通知を既読にする
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        $count = $this->notificationService->markAllAsRead($user);

        return redirect()
            ->back()
            ->with('success', "{$count}件の通知を既読にしました。");
    }
}