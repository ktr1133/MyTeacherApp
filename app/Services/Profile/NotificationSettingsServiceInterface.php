<?php

namespace App\Services\Profile;

use App\Models\User;

/**
 * 通知設定サービスインターフェース
 */
interface NotificationSettingsServiceInterface
{
    /**
     * ユーザーの通知設定を取得
     * 
     * @param User $user ユーザー
     * @return array<string, bool> 通知設定（7項目）
     */
    public function getSettings(User $user): array;

    /**
     * ユーザーの通知設定を更新
     * 
     * @param User $user ユーザー
     * @param array<string, bool> $settings 更新する設定（部分更新可）
     * @return array<string, bool> 更新後の通知設定
     */
    public function updateSettings(User $user, array $settings): array;
}
