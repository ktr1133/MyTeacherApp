<?php

namespace App\Http\Actions\Admin\Notification;

use App\Http\Responders\Admin\AdminNotificationResponder;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 管理者通知作成画面表示アクション
 * 
 * 通知作成フォームを表示。
 * 
 * @package App\Http\Actions\Admin\Notification
 */
class CreateAdminNotificationAction
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
     * 通知作成画面を表示
     *
     * @param Request $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        $users = User::select('id', 'username')->orderBy('username')->get();
        $groups = Group::select('id', 'name')->orderBy('name')->get();

        return $this->responder->create($users, $groups);
    }
}