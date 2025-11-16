<?php

namespace App\Repositories\Notification;

use App\Models\NotificationTemplate;
use App\Models\UserNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * 通知リポジトリのインターフェース
 * 
 * 通知データの永続化・取得を担当。
 * 
 * @package App\Repositories
 */
interface NotificationRepositoryInterface
{
    /**
     * ユーザーの通知一覧を取得
     * 
     * ページネーション付きでユーザーの通知を取得。
     * 通知テンプレートとのリレーションを eager loading する。
     *
     * @param int $userId ユーザーID
     * @param int $perPage 1ページあたりの件数（デフォルト: 20）
     * @return LengthAwarePaginator ページネーション済みの通知一覧
     */
    public function getUserNotifications(int $userId, int $perPage = 20): LengthAwarePaginator;

    /**
     * ユーザーの未読通知件数を取得
     *
     * @param int $userId ユーザーID
     * @return int 未読通知件数
     */
    public function getUnreadCount(int $userId): int;

    /**
     * 通知を既読にする
     *
     * @param int $userNotificationId ユーザー通知ID
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function markAsRead(int $userNotificationId): void;

    /**
     * 全通知を既読にする
     * 
     * 指定ユーザーの未読通知をすべて既読に更新。
     *
     * @param int $userId ユーザーID
     * @return void
     */
    public function markAllAsRead(int $userId): void;

    /**
     * 通知テンプレートを作成
     *
     * @param array $data 通知データ
     * @return NotificationTemplate 作成された通知テンプレート
     */
    public function createTemplate(array $data): NotificationTemplate;

    /**
     * 通知テンプレートを更新
     * 
     * 編集履歴として updated_by を記録。
     *
     * @param int $templateId 通知テンプレートID
     * @param array $data 更新データ
     * @param int $updatedBy 編集者のユーザーID
     * @return NotificationTemplate 更新後の通知テンプレート
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function updateTemplate(int $templateId, array $data, int $updatedBy): NotificationTemplate;

    /**
     * 通知テンプレートを削除（ソフトデリート）
     *
     * @param int $templateId 通知テンプレートID
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deleteTemplate(int $templateId): void;

    /**
     * 期限切れ通知を物理削除
     * 
     * expire_at から 30 日経過した通知を完全削除。
     *
     * @return int 削除された件数
     */
    public function deleteExpiredNotifications(): int;

    /**
     * 対象ユーザーに通知を配信
     * 
     * target_type に基づいてユーザーを抽出し、
     * user_notifications テーブルにレコードを一括挿入。
     *
     * @param NotificationTemplate $template 通知テンプレート
     * @return int 配信された通知件数
     */
    public function distributeNotification(NotificationTemplate $template): int;

    /**
     * 指定日時以降の新規通知を取得
     *
     * @param int $userId ユーザーID
     * @param string|null $since 取得開始日時（ISO8601形式）
     * @param int $limit 取得件数
     * @return Collection
     */
    public function getNewNotificationsSince(int $userId, ?string $since = null, int $limit = 5): Collection;
}