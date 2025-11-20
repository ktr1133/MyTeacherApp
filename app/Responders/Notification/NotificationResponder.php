<?php

namespace App\Responders\Notification;

use Illuminate\Contracts\View\View;

/**
 * 通知一覧画面レスポンダー
 */
class NotificationResponder
{
    /**
     * レスポンスを返す
     *
     * @param array $data
     * @return View
     */
    public function response(array $data): View
    {
        return view('notifications.index', $data);
    }
}