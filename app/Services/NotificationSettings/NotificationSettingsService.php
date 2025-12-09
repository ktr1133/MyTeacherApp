<?php

namespace App\Services\NotificationSettings;

use App\Repositories\NotificationSettings\NotificationSettingsRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * 通知設定サービス
 */
class NotificationSettingsService implements NotificationSettingsServiceInterface
{
    /**
     * カテゴリとキーのマッピング
     */
    private const CATEGORY_KEY_MAP = [
        'task' => 'push_task_enabled',
        'group' => 'push_group_enabled',
        'token' => 'push_token_enabled',
        'system' => 'push_system_enabled',
    ];

    /**
     * @param NotificationSettingsRepositoryInterface $repository
     */
    public function __construct(
        protected NotificationSettingsRepositoryInterface $repository
    ) {}

    /**
     * ユーザーの通知設定を取得
     *
     * @param int $userId
     * @return array
     */
    public function getSettings(int $userId): array
    {
        try {
            return $this->repository->getSettings($userId);
        } catch (\Exception $e) {
            Log::error('通知設定取得エラー', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            // エラー時はデフォルト設定を返す
            return $this->repository->getDefaultSettings();
        }
    }

    /**
     * ユーザーの通知設定を更新
     *
     * @param int $userId
     * @param array $settings
     * @return array 更新後の設定
     * @throws ValidationException
     */
    public function updateSettings(int $userId, array $settings): array
    {
        // 設定キーのバリデーション
        $validKeys = [
            'push_enabled',
            'push_task_enabled',
            'push_group_enabled',
            'push_token_enabled',
            'push_system_enabled',
            'push_sound_enabled',
            'push_vibration_enabled',
        ];

        foreach (array_keys($settings) as $key) {
            if (!in_array($key, $validKeys)) {
                throw ValidationException::withMessages([
                    'settings' => "不正な設定キーが含まれています: {$key}",
                ]);
            }
        }

        // 値のバリデーション（すべてboolean）
        foreach ($settings as $key => $value) {
            if (!is_bool($value)) {
                throw ValidationException::withMessages([
                    $key => "設定値はtrue/falseのいずれかを指定してください。",
                ]);
            }
        }

        try {
            $this->repository->updateSettings($userId, $settings);

            Log::info('通知設定更新完了', [
                'user_id' => $userId,
                'settings' => $settings,
            ]);

            return $this->repository->getSettings($userId);
        } catch (\Exception $e) {
            Log::error('通知設定更新エラー', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * 特定カテゴリのPush通知が有効か確認
     *
     * @param int $userId
     * @param string $category 'task' | 'group' | 'token' | 'system'
     * @return bool
     */
    public function isPushEnabled(int $userId, string $category): bool
    {
        // カテゴリのバリデーション
        if (!isset(self::CATEGORY_KEY_MAP[$category])) {
            Log::warning('不正なカテゴリ指定', [
                'category' => $category,
            ]);
            return false;
        }

        $settings = $this->getSettings($userId);

        // 全体のPush通知がOFFの場合はfalse
        if (!($settings['push_enabled'] ?? true)) {
            return false;
        }

        // カテゴリ別の設定を確認
        $key = self::CATEGORY_KEY_MAP[$category];
        return $settings[$key] ?? true;
    }
}
