<?php

namespace App\Http\Actions\Notification;

use App\Http\Responders\Notification\NotificationResponder;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 通知詳細表示アクション
 * 
 * 通知の詳細を表示し、自動的に既読処理を行う。
 * 
 * @package App\Http\Actions\Notification
 */
class ShowNotificationAction
{
    /**
     * コンストラクタ
     *
     * @param NotificationResponder $responder レスポンダ
     */
    public function __construct(
        private NotificationResponder $responder
    ) {}

    /**
     * 通知詳細を表示
     *
     * @param Request $request
     * @param UserNotification $notification ユーザー通知
     * @return View|RedirectResponse
     */
    public function __invoke(Request $request, UserNotification $notification): View|RedirectResponse
    {
        // 認可チェック（自分の通知のみ表示可能）
        if ($notification->user_id !== $request->user()->id) {
            return redirect()->route('notifications.index')
                ->with('error', '権限がありません。');
        }

        // 未読の場合、既読にする
        if (!$notification->is_read) {
            $notification->markAsRead();
        }

        $template = $notification->template;
        $isDeleted = $template === null || $template->trashed();

        return $this->responder->show($notification, $template, $isDeleted);
    }
}