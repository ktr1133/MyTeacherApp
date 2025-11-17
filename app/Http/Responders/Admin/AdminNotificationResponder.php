<?php

namespace App\Http\Responders\Admin;

use App\Models\Group;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * 管理者通知レスポンダクラス
 * 
 * 管理者向け通知管理画面のビューまたはリダイレクトレスポンスを生成。
 * 
 * @package App\Http\Responders\Admin
 */
class AdminNotificationResponder
{
    /**
     * 通知一覧ビューを返す
     *
     * @param LengthAwarePaginator $notifications 通知一覧
     * @return View
     */
    public function index(LengthAwarePaginator $notifications): View
    {
        return view('admin.notifications.index', compact('notifications'));
    }

    /**
     * 通知作成ビューを返す
     *
     * @param \Illuminate\Database\Eloquent\Collection $users ユーザー一覧
     * @param \Illuminate\Database\Eloquent\Collection $groups グループ一覧
     * @return View
     */
    public function create($users, $groups): View
    {
        return view('admin.notifications.create', compact('users', 'groups'));
    }

    /**
     * 通知作成後のリダイレクト
     *
     * @param NotificationTemplate $template 作成された通知
     * @param int $distributedCount 配信件数
     * @return RedirectResponse
     */
    public function store(NotificationTemplate $template, int $distributedCount): RedirectResponse
    {
        return redirect()
            ->route('admin.notifications.index')
            ->with('success', "通知を作成し、{$distributedCount}件配信しました。");
    }

    /**
     * 通知編集ビューを返す
     *
     * @param NotificationTemplate $notification 通知テンプレート
     * @param \Illuminate\Database\Eloquent\Collection $users ユーザー一覧
     * @param \Illuminate\Database\Eloquent\Collection $groups グループ一覧
     * @return View
     */
    public function edit(NotificationTemplate $notification, $users, $groups): View
    {
        return view('admin.notifications.edit', compact('notification', 'users', 'groups'));
    }

    /**
     * 通知更新後のリダイレクト
     *
     * @param NotificationTemplate $template 更新された通知
     * @return RedirectResponse
     */
    public function update(NotificationTemplate $template): RedirectResponse
    {
        return redirect()
            ->route('admin.notifications.index')
            ->with('success', '通知を更新しました。');
    }

    /**
     * 通知削除後のリダイレクト
     *
     * @return RedirectResponse
     */
    public function delete(): RedirectResponse
    {
        return redirect()
            ->route('admin.notifications.index')
            ->with('success', '通知を削除しました。');
    }
}