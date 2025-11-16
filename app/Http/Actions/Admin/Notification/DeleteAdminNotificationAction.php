<?php

namespace App\Http\Actions\Admin\Notification;

use App\Http\Responders\Admin\AdminNotificationResponder;
use App\Models\NotificationTemplate;
use App\Services\Notification\NotificationServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 管理者通知削除アクション
 * 
 * 通知をソフトデリート。
 * 
 * @package App\Http\Actions\Admin\Notification
 */
class DeleteAdminNotificationAction
{
    /**
     * コンストラクタ
     *
     * @param NotificationServiceInterface $service 通知サービス
     * @param AdminNotificationResponder $responder レスポンダ
     */
    public function __construct(
        private NotificationServiceInterface $service,
        private AdminNotificationResponder $responder
    ) {}

    /**
     * 通知を削除
     *
     * @param Request $request
     * @param NotificationTemplate $notification 通知テンプレート
     * @return RedirectResponse
     */
    public function __invoke(Request $request, NotificationTemplate $notification): RedirectResponse
    {
        $this->service->deleteNotification($notification->id);

        return $this->responder->delete();
    }
}