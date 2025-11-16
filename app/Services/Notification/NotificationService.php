<?php

namespace App\Services\Notification;

use App\Models\NotificationTemplate;
use App\Repositories\Notification\NotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * 通知サービスの実装クラス
 * 
 * 通知に関するビジネスロジックを担当。
 * 
 * @package App\Services
 */
class NotificationService implements NotificationServiceInterface
{
    /**
     * コンストラクタ
     *
     * @param NotificationRepositoryInterface $repository 通知リポジトリ
     */
    public function __construct(
        private NotificationRepositoryInterface $repository
    ) {}

    /**
     * ユーザーの通知一覧を取得
     *
     * @param int $userId ユーザーID
     * @param int $perPage 1ページあたりの件数
     * @return LengthAwarePaginator
     */
    public function getUserNotifications(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->getUserNotifications($userId, $perPage);
    }

    /**
     * 未読通知件数を取得
     *
     * @param int $userId ユーザーID
     * @return int
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->repository->getUnreadCount($userId);
    }

    /**
     * 通知を既読にする
     *
     * @param int $userNotificationId ユーザー通知ID
     * @return void
     */
    public function markAsRead(int $userNotificationId): void
    {
        $this->repository->markAsRead($userNotificationId);
    }

    /**
     * 全通知を既読にする
     *
     * @param int $userId ユーザーID
     * @return void
     */
    public function markAllAsRead(int $userId): void
    {
        $this->repository->markAllAsRead($userId);
    }

    /**
     * 管理者通知を作成・配信
     * 
     * トランザクション内で通知テンプレートを作成し、対象ユーザーに配信。
     *
     * @param array $data 通知データ
     * @param int $senderId 送信者（管理者）のユーザーID
     * @return NotificationTemplate
     */
    public function createAndDistributeNotification(array $data, int $senderId): NotificationTemplate
    {
        return DB::transaction(function () use ($data, $senderId) {
            $data['sender_id'] = $senderId;
            $data['source'] = 'admin';
            
            $template = $this->repository->createTemplate($data);
            $this->repository->distributeNotification($template);
            
            return $template;
        });
    }

    /**
     * 管理者通知を更新
     *
     * @param int $templateId 通知テンプレートID
     * @param array $data 更新データ
     * @param int $updatedBy 編集者のユーザーID
     * @return NotificationTemplate
     */
    public function updateNotification(int $templateId, array $data, int $updatedBy): NotificationTemplate
    {
        return $this->repository->updateTemplate($templateId, $data, $updatedBy);
    }

    /**
     * 管理者通知を削除
     *
     * @param int $templateId 通知テンプレートID
     * @return void
     */
    public function deleteNotification(int $templateId): void
    {
        $this->repository->deleteTemplate($templateId);
    }

    /**
     * 期限切れ通知を削除
     *
     * @return int 削除された件数
     */
    public function cleanupExpiredNotifications(): int
    {
        return $this->repository->deleteExpiredNotifications();
    }

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
    public function sendNotification(int $senderId, int $recipientId, string $notificationType, string $title, string $message, string $priority = 'normal'): void
    {
        $data = [
            'sender_id'   => $senderId,
            'source'      => 'system',
            'type'        => $notificationType,
            'title'       => $title,
            'target_type' => 'users',
            'target_ids'  => json_encode([$recipientId]),
            'message'     => $message,
            'priority'    => $priority,
            'publish_at'  => now(),
            'expire_at'   => Carbon::parse(now())->addDays(30),
            'updated_by'  => $senderId,
        ];
        
        DB::transaction(function () use ($data) {
            // 通知テンプレートを作成
            $template = $this->repository->createTemplate($data);
            // ユーザに通知を送信
            $this->repository->distributeNotification($template);
        });
    }

    /**
     * 未読件数と新規通知を取得（API用）
     *
     * @param int $userId ユーザーID
     * @param string|null $lastCheckedAt 最後のチェック日時（ISO8601形式）
     * @return array ['unread_count' => int, 'new_notifications' => array, 'timestamp' => string]
     */
    public function getUnreadCountWithNew(int $userId, ?string $lastCheckedAt = null): array
    {
        // 未読件数
        $unreadCount = $this->repository->getUnreadCount($userId);

        // 最後のチェック以降の新規通知（最大5件）
        $newNotifications = collect();
        if ($lastCheckedAt) {
            $notifications = $this->repository->getNewNotificationsSince($userId, $lastCheckedAt, 5);
            
            $newNotifications = $notifications->map(function ($notification) {
                $template = $notification->template;
                
                return [
                    'id' => $notification->id,
                    'title' => $template?->title ?? '[削除された通知]',
                    'priority' => $template?->priority ?? 'normal',
                    'sender' => $template?->sender?->username ?? '不明',
                    'created_at' => $notification->created_at->toIso8601String(),
                    'is_deleted' => $template === null || $template->trashed(),
                ];
            });
        }

        return [
            'unread_count' => $unreadCount,
            'new_notifications' => $newNotifications->toArray(),
            'timestamp' => now()->toIso8601String(),
        ];
    }
}