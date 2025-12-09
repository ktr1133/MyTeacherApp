<?php

namespace App\Services\DeviceToken;

use App\Models\UserDeviceToken;
use Illuminate\Support\Collection;

/**
 * デバイストークン管理サービスインターフェース
 */
interface DeviceTokenManagementServiceInterface
{
    /**
     * FCMトークンを登録または更新
     *
     * @param int $userId
     * @param string $deviceToken
     * @param string $deviceType 'ios' | 'android'
     * @param string|null $deviceName
     * @param string|null $appVersion
     * @return UserDeviceToken
     */
    public function registerToken(
        int $userId,
        string $deviceToken,
        string $deviceType,
        ?string $deviceName = null,
        ?string $appVersion = null
    ): UserDeviceToken;

    /**
     * FCMトークンを削除
     *
     * @param int $userId
     * @param string $deviceToken
     * @return bool
     */
    public function deleteToken(int $userId, string $deviceToken): bool;

    /**
     * ユーザーのアクティブなデバイストークンを取得
     *
     * @param int $userId
     * @return Collection<int, UserDeviceToken>
     */
    public function getActiveTokens(int $userId): Collection;

    /**
     * デバイストークンを非アクティブ化（FCMエラー時）
     *
     * @param string $deviceToken
     * @return bool
     */
    public function deactivateToken(string $deviceToken): bool;
}
