<?php

namespace App\Repositories\NotificationSettings;

use App\Models\User;

/**
 * 通知設定Eloquentリポジトリ
 */
class NotificationSettingsEloquentRepository implements NotificationSettingsRepositoryInterface
{
    /**
     * ユーザーの通知設定を取得
     *
     * @param int $userId
     * @return array
     */
    public function getSettings(int $userId): array
    {
        $user = User::findOrFail($userId);
        return $user->notification_settings ?? $this->getDefaultSettings();
    }

    /**
     * ユーザーの通知設定を更新
     *
     * @param int $userId
     * @param array $settings
     * @return bool
     */
    public function updateSettings(int $userId, array $settings): bool
    {
        $user = User::findOrFail($userId);
        $user->notification_settings = array_merge(
            $this->getDefaultSettings(),
            $settings
        );
        return $user->save();
    }

    /**
     * デフォルト通知設定を取得
     *
     * @return array
     */
    public function getDefaultSettings(): array
    {
        return [
            'push_enabled' => true,
            'push_task_enabled' => true,
            'push_group_enabled' => true,
            'push_token_enabled' => true,
            'push_system_enabled' => true,
            'push_sound_enabled' => true,
            'push_vibration_enabled' => true,
        ];
    }
}
