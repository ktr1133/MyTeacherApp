<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Models\Notification;
use App\Repositories\Token\TokenRepositoryInterface;

/**
 * 通知サービス インターフェース
 */
interface NotificationServiceInterface
{
    /**
     * 通知を既読にする
     *
     * @param int $notificationId
     * @param User $user
     * @return bool
     */
    public function markAsRead(int $notificationId, User $user): bool;

    /**
     * 全通知を既読にする
     *
     * @param User $user
     * @return int 更新件数
     */
    public function markAllAsRead(User $user): int;
}