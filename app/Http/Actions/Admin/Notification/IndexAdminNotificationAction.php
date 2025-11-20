<?php

namespace App\Http\Actions\Admin\Notification;

use App\Http\Responders\Admin\AdminNotificationResponder;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 管理者通知一覧表示アクション
 * 
 * 管理者が作成した通知の一覧を表示。
 * 
 * @package App\Http\Actions\Admin\Notification
 */
class IndexAdminNotificationAction
{
    /**
     * コンストラクタ
     *
     * @param AdminNotificationResponder $responder レスポンダ
     */
    public function __construct(
        private AdminNotificationResponder $responder
    ) {}

    /**
     * 通知一覧を表示
     *
     * @param Request $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        $notifications = NotificationTemplate::with(['sender', 'updatedBy'])
            ->admin()
            ->latest()
            ->paginate(20);

        return $this->responder->index($notifications);
    }
}