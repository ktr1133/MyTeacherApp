<?php

namespace App\Services\Notification;

use App\Models\NotificationTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 通知サービスのインターフェース
 * 
 * 通知に関するビジネスロジックを担当。
 * 
 * @package App\Services
 */
interface NotificationServiceInterface
{
    /**
     * ユーザーの通知一覧を取得
     *
     * @param int $userId ユーザーID
     * @param int $perPage 1ページあたりの件数（デフォルト: 20）
     * @return LengthAwarePaginator ページネーション済みの通知一覧
     */
    public function getUserNotifications(int $userId, int $perPage = 20): LengthAwarePaginator;

    /**
     * 未読通知件数を取得
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
     * @param int $userId ユーザーID
     * @return void
     */
    public function markAllAsRead(int $userId): void;

    /**
     * 管理者通知を作成・配信
     * 
     * トランザクション内で通知テンプレートを作成し、
     * 対象ユーザーに配信する。
     *
     * @param array $data 通知データ
     * @param int $senderId 送信者（管理者）のユーザーID
     * @return NotificationTemplate 作成された通知テンプレート
     */
    public function createAndDistributeNotification(array $data, int $senderId): NotificationTemplate;

    /**
     * 管理者通知を更新
     *
     * @param int $templateId 通知テンプレートID
     * @param array $data 更新データ
     * @param int $updatedBy 編集者のユーザーID
     * @return NotificationTemplate 更新後の通知テンプレート
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function updateNotification(int $templateId, array $data, int $updatedBy): NotificationTemplate;

    /**
     * 管理者通知を削除
     *
     * @param int $templateId 通知テンプレートID
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deleteNotification(int $templateId): void;

    /**
     * 期限切れ通知を削除
     * 
     * 定期バッチから呼び出される。
     *
     * @return int 削除された件数
     */
    public function cleanupExpiredNotifications(): int;

    /**
     * 対象ユーザーに通知を配信
     *
     * @param int $senderId 送信者ユーザーID
     * @param int $recipientId 受信者ユーザーID
     * @param string $notificationType 通知タイプ
     * @param string $title 通知タイトル
     * @param string $message 通知メッセージ
     * @param string $priority 通知の優先度（info, normal, important）
     * @return void
     */
    public function sendNotification(int $senderId, int $recipientId, string $notificationType, string $title, string $message, string $priority = 'normal'): void;

    /**
     * 対象グループに通知を配信
     *
     * @param string $notificationType 通知タイプ
     * @param string $title 通知タイトル
     * @param string $message 通知メッセージ
     * @param string $priority 通知の優先度（info, normal, important）
     * @return void
     */
    public function sendNotificationForGroup(string $notificationType, string $title, string $message, string $priority = 'normal'): void;

    /**
     * 未読件数と新規通知を取得（API用）
     *
     * @param int $userId ユーザーID
     * @param string|null $lastCheckedAt 最後のチェック日時（ISO8601形式）
     * @return array ['unread_count' => int, 'new_notifications' => array, 'timestamp' => string]
     */
    public function getUnreadCountWithNew(int $userId, ?string $lastCheckedAt = null): array;
}