<?php

namespace App\Http\Actions\Notification;

use App\Http\Responders\Notification\NotificationResponder;
use App\Services\Notification\NotificationServiceInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 通知一覧表示アクション
 * 
 * ユーザーの通知一覧を表示。
 * 
 * @package App\Http\Actions\Notification
 */
class IndexNotificationAction
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
     * 通知一覧を表示
     *
     * @param Request $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        $userId = $request->user()->id;
        
        $notifications = $this->service->getUserNotifications($userId, 15);
        $unreadCount = $this->service->getUnreadCount($userId);

        return $this->responder->index($notifications, $unreadCount);
    }
}