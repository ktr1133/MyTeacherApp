<?php

namespace App\Repositories\Profile;

use App\Models\User;

/**
 * 通知設定リポジトリインターフェース
 */
interface NotificationSettingsRepositoryInterface
{
    /**
     * ユーザーの通知設定を取得
     * 
     * @param User $user ユーザー
     * @return array<string, bool> 通知設定
     */
    public function getSettings(User $user): array;

    /**
     * ユーザーの通知設定を更新
     * 
     * @param User $user ユーザー
     * @param array<string, bool> $settings 通知設定
     * @return void
     */
    public function updateSettings(User $user, array $settings): void;
}
