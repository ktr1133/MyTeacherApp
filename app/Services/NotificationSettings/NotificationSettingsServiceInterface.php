<?php

namespace App\Services\NotificationSettings;

/**
 * 通知設定サービスインターフェース
 */
interface NotificationSettingsServiceInterface
{
    /**
     * ユーザーの通知設定を取得
     *
     * @param int $userId
     * @return array
     */
    public function getSettings(int $userId): array;

    /**
     * ユーザーの通知設定を更新
     *
     * @param int $userId
     * @param array $settings
     * @return array 更新後の設定
     */
    public function updateSettings(int $userId, array $settings): array;

    /**
     * 特定カテゴリのPush通知が有効か確認
     *
     * @param int $userId
     * @param string $category 'task' | 'group' | 'token' | 'system'
     * @return bool
     */
    public function isPushEnabled(int $userId, string $category): bool;
}
