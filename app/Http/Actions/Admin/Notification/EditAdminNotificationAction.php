<?php

namespace App\Http\Actions\Admin\Notification;

use App\Http\Responders\Admin\AdminNotificationResponder;
use App\Models\Group;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 管理者通知編集画面表示アクション
 * 
 * 通知編集フォームを表示。
 * 
 * @package App\Http\Actions\Admin\Notification
 */
class EditAdminNotificationAction
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
     * 通知編集画面を表示
     *
     * @param Request $request
     * @param NotificationTemplate $notification 通知テンプレート
     * @return View
     */
    public function __invoke(Request $request, NotificationTemplate $notification): View
    {
        $users = User::select('id', 'username')->orderBy('username')->get();
        $groups = Group::select('id', 'name')->orderBy('name')->get();

        return $this->responder->edit($notification, $users, $groups);
    }
}