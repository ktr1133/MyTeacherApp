<?php

namespace App\Http\Actions\Notification;

use App\Http\Responders\Notification\NotificationResponder;
use App\Services\Notification\NotificationServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 全通知既読化アクション
 * 
 * ユーザーのすべての未読通知を既読にする。
 * 
 * @package App\Http\Actions\Notification
 */
class MarkAllNotificationsAsReadAction
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
     * すべての通知を既読にする
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $this->service->markAllAsRead($request->user()->id);

        return $this->responder->markAllAsRead();
    }
}