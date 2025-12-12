<?php

namespace App\Services\Profile;

use App\Models\User;
use App\Repositories\Profile\NotificationSettingsRepositoryInterface;

/**
 * 通知設定サービス
 * 
 * ユーザーの通知設定の取得・更新を管理
 */
class NotificationSettingsService implements NotificationSettingsServiceInterface
{
    /**
     * デフォルト通知設定
     */
    private const DEFAULT_SETTINGS = [
        'push_enabled' => true,
        'push_task_enabled' => true,
        'push_group_enabled' => true,
        'push_token_enabled' => true,
        'push_system_enabled' => true,
        'push_sound_enabled' => true,
        'push_vibration_enabled' => true,
    ];

    /**
     * コンストラクタ
     *
     * @param NotificationSettingsRepositoryInterface $repository
     */
    public function __construct(
        protected NotificationSettingsRepositoryInterface $repository
    ) {}

    /**
     * ユーザーの通知設定を取得
     * 
     * 未設定の場合はデフォルト値を返す
     * 
     * @param User $user ユーザー
     * @return array<string, bool> 通知設定（7項目）
     */
    public function getSettings(User $user): array
    {
        $settings = $this->repository->getSettings($user);

        // 未設定の場合はデフォルト値を返す
        if (empty($settings)) {
            return self::DEFAULT_SETTINGS;
        }

        // デフォルト値とマージ（不足している項目を補完）
        return array_merge(self::DEFAULT_SETTINGS, $settings);
    }

    /**
     * ユーザーの通知設定を更新
     * 
     * 部分更新に対応（指定された項目のみ更新）
     * 
     * @param User $user ユーザー
     * @param array<string, bool> $settings 更新する設定（部分更新可）
     * @return array<string, bool> 更新後の通知設定
     */
    public function updateSettings(User $user, array $settings): array
    {
        // 現在の設定を取得
        $currentSettings = $this->getSettings($user);

        // 指定された項目のみを更新（マージ）
        $updatedSettings = array_merge($currentSettings, $settings);

        // Repository経由で更新
        $this->repository->updateSettings($user, $updatedSettings);

        return $updatedSettings;
    }
}
