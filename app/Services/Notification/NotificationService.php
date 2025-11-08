<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Repositories\Token\TokenRepositoryInterface;

/**
 * 通知サービス実装
 * 
 * 通知の既読管理などのビジネスロジックを提供します。
 * データアクセスは全てRepositoryを経由します。
 */
class NotificationService implements NotificationServiceInterface
{
    public function __construct(
        private TokenRepositoryInterface $tokenRepository
    ) {}

    /**
     * {@inheritdoc}
     */
    public function markAsRead(int $notificationId, User $user): bool
    {
        $notification = $this->tokenRepository->findNotification($notificationId);

        if (!$notification) {
            return false;
        }

        // 権限チェック
        if ($notification->user_id !== $user->id) {
            return false;
        }

        if (!$notification->is_read) {
            return $this->tokenRepository->updateNotification($notification, [
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function markAllAsRead(User $user): int
    {
        return $this->tokenRepository->markAllNotificationsAsRead($user->id);
    }
}