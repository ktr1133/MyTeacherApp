<?php

namespace App\Http\Actions\Notification;

use App\Http\Responders\Notification\NotificationResponder;
use App\Models\UserNotification;
use App\Services\Notification\NotificationServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 通知既読化アクション
 * 
 * 指定された通知を既読にする。
 * 
 * @package App\Http\Actions\Notification
 */
class MarkNotificationAsReadAction
{
    /**
     * コンストラクタ
     *
     * @param NotificationServiceInterface $service 通知サービス
     * @param NotificationResponder $responder レスポンダ
     */
    public function __construct(
        private NotificationServiceInterface $service,
        private NotificationResponder $responder
    ) {}

    /**
     * 通知を既読にする
     *
     * @param Request $request
     * @param UserNotification $notification ユーザー通知
     * @return RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function __invoke(Request $request, UserNotification $notification): RedirectResponse
    {
        // 本人の通知であることを確認
        if ($notification->user_id !== $request->user()->id) {
            abort(403, 'この通知を既読にする権限がありません。');
        }

        $this->service->markAsRead($notification->id);

        return $this->responder->markAsRead();
    }
}