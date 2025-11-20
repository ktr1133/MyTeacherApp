<?php

namespace App\Http\Responders\Notification;

use App\Models\NotificationTemplate;
use App\Models\UserNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * 通知レスポンダ
 * 
 * ユーザー向け通知画面のレスポンスを生成。
 * 
 * @package App\Http\Responders\Notification
 */
class NotificationResponder
{
    /**
     * 通知一覧画面
     *
     * @param \Illuminate\Contracts\Pagination\LengthAwarePaginator $notifications
     * @param int $unreadCount
     * @return View
     */
    public function index(LengthAwarePaginator $notifications, int $unreadCount): View
    {
        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * 通知詳細画面
     *
     * @param UserNotification $notification ユーザー通知
     * @param NotificationTemplate|null $template 通知テンプレート
     * @param bool $isDeleted 削除済みフラグ
     * @return View
     */
    public function show(UserNotification $notification, ?NotificationTemplate $template, bool $isDeleted): View
    {
        return view('notifications.show', compact('notification', 'template', 'isDeleted'));
    }

    /**
     * 既読処理後のレスポンス
     *
     * @return RedirectResponse
     */
    public function markAsRead(): RedirectResponse
    {
        return redirect()->route('notifications.index')
            ->with('success', '通知を既読にしました。');
    }

    /**
     * 全既読処理後のレスポンス
     *
     * @return RedirectResponse
     */
    public function markAllAsRead(): RedirectResponse
    {
        return redirect()->route('notifications.index')
            ->with('success', 'すべての通知を既読にしました。');
    }

    /**
     * 検索結果画面
     *
     * @param LengthAwarePaginator $notifications
     * @param array $searchTerms 検索語句
     * @param string $operator 検索演算子
     * @return View
     */
    public function searchResults(LengthAwarePaginator $notifications,  array $searchTerms, string $operator): View
    {
        return view('notifications.search-results', compact('notifications', 'searchTerms', 'operator'));
    }
}