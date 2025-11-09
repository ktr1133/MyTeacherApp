<?php

namespace App\Http\Actions\Notification;

use App\Services\Notification\NotificationServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

/**
 * 通知既読化アクション
 */
class MarkNotificationAsReadAction
{
    public function __construct(
        private NotificationServiceInterface $notificationService
    ) {}

    /**
     * 通知を既読にする
     *
     * @param Request $request
     * @param int $notificationId
     * @return RedirectResponse
     */
    public function __invoke(Request $request, int $notificationId): RedirectResponse
    {
        $user = $request->user();
        
        $success = $this->notificationService->markAsRead($notificationId, $user);

        if ($success) {
            return redirect()
                ->back()
                ->with('success', '通知を既読にしました。');
        }

        return redirect()
            ->back()
            ->with('error', '通知が見つかりません。');
    }
}