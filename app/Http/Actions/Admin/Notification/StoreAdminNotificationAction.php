<?php

namespace App\Http\Actions\Admin\Notification;

use App\Http\Requests\Notification\StoreNotificationRequest;
use App\Http\Responders\Admin\AdminNotificationResponder;
use App\Services\Notification\NotificationServiceInterface;
use Illuminate\Http\RedirectResponse;

/**
 * 管理者通知作成アクション
 * 
 * 通知を作成し、対象ユーザーに配信。
 * 
 * @package App\Http\Actions\Admin\Notification
 */
class StoreAdminNotificationAction
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
     * 通知を作成・配信
     *
     * @param StoreNotificationRequest $request
     * @return RedirectResponse
     */
    public function __invoke(StoreNotificationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $senderId = $request->user()->id;

        $template = $this->service->createAndDistributeNotification($data, $senderId);
        $distributedCount = $template->userNotifications()->count();

        // アバターイベントをセッションに保存
        session(['avatar_event' => 'notification_created']);

        return $this->responder->store($template, $distributedCount);
    }
}