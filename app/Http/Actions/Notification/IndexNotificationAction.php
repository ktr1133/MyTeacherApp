<?php

namespace App\Http\Actions\Notification;

use App\Repositories\Token\TokenRepositoryInterface;
use App\Responders\Notification\NotificationResponder;
use Illuminate\Http\Request;

/**
 * 通知一覧表示アクション
 */
class IndexNotificationAction
{
    public function __construct(
        private TokenRepositoryInterface $tokenRepository,
        private NotificationResponder $responder
    ) {}

    /**
     * 通知一覧を表示
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $notifications = $this->tokenRepository->getUserNotifications($user->id, 20);
        $unreadCount = $this->tokenRepository->getUnreadNotificationCount($user->id);

        return $this->responder->response([
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }
}