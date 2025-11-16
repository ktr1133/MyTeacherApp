<?php

namespace App\Http\Actions\Admin\Notification;

use App\Http\Requests\Notification\UpdateNotificationRequest;
use App\Http\Responders\Admin\AdminNotificationResponder;
use App\Models\NotificationTemplate;
use App\Services\Notification\NotificationServiceInterface;
use Illuminate\Http\RedirectResponse;

/**
 * 管理者通知更新アクション
 * 
 * 既存の通知を更新。
 * 
 * @package App\Http\Actions\Admin\Notification
 */
class UpdateAdminNotificationAction
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
     * 通知を更新
     *
     * @param UpdateNotificationRequest $request
     * @param NotificationTemplate $notification 通知テンプレート
     * @return RedirectResponse
     */
    public function __invoke(UpdateNotificationRequest $request, NotificationTemplate $notification): RedirectResponse
    {
        $data = $request->validated();
        $updatedBy = $request->user()->id;

        $template = $this->service->updateNotification($notification->id, $data, $updatedBy);

        // アバターイベントをセッションに保存
        session()->flash('avatar_event', 'notification_updated');
        
        return $this->responder->update($template);
    }
}