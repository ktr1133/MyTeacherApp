<?php

namespace App\Repositories\NotificationSettings;

/**
 * 通知設定リポジトリインターフェース
 */
interface NotificationSettingsRepositoryInterface
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
     * @return bool
     */
    public function updateSettings(int $userId, array $settings): bool;

    /**
     * デフォルト通知設定を取得
     *
     * @return array
     */
    public function getDefaultSettings(): array;
}
