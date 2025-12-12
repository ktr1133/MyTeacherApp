<?php

namespace App\Repositories\Profile;

use App\Models\User;

/**
 * 通知設定リポジトリ（Eloquent実装）
 * 
 * usersテーブルのnotification_settingsカラムへのCRUD操作を担当
 */
class NotificationSettingsEloquentRepository implements NotificationSettingsRepositoryInterface
{
    /**
     * ユーザーの通知設定を取得
     * 
     * @param User $user ユーザー
     * @return array<string, bool> 通知設定（空配列の場合あり）
     */
    public function getSettings(User $user): array
    {
        $settings = $user->notification_settings;

        // null or 空配列の場合は空配列を返す
        return is_array($settings) ? $settings : [];
    }

    /**
     * ユーザーの通知設定を更新
     * 
     * @param User $user ユーザー
     * @param array<string, bool> $settings 通知設定
     * @return void
     */
    public function updateSettings(User $user, array $settings): void
    {
        $user->notification_settings = $settings;
        $user->save();
    }
}
