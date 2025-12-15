<?php

namespace App\Repositories\DeviceToken;

use App\Models\UserDeviceToken;
use Illuminate\Support\Collection;

/**
 * FCMデバイストークンリポジトリインターフェース
 */
interface DeviceTokenRepositoryInterface
{
    /**
     * デバイストークンを登録または更新
     *
     * @param int $userId
     * @param string $deviceToken
     * @param string $deviceType 'ios' | 'android'
     * @param string|null $deviceName
     * @param string|null $appVersion
     * @return UserDeviceToken
     */
    public function registerOrUpdate(
        int $userId,
        string $deviceToken,
        string $deviceType,
        ?string $deviceName = null,
        ?string $appVersion = null
    ): UserDeviceToken;

    /**
     * デバイストークンを削除（is_active = FALSE）
     *
     * @param int $userId
     * @param string $deviceToken
     * @return bool
     */
    public function delete(int $userId, string $deviceToken): bool;

    /**
     * ユーザーのアクティブなデバイストークンを取得
     *
     * @param int $userId
     * @return Collection<int, UserDeviceToken>
     */
    public function getActiveTokensByUserId(int $userId): Collection;

    /**
     * デバイストークンが存在するか確認
     *
     * @param string $deviceToken
     * @return bool
     */
    public function exists(string $deviceToken): bool;

    /**
     * デバイストークンを非アクティブ化
     *
     * @param string $deviceToken
     * @return bool
     */
    public function deactivate(string $deviceToken): bool;

    /**
     * ユーザーの全デバイストークンを取得（is_activeに関わらず）
     *
     * @param int $userId
     * @return Collection<int, UserDeviceToken>
     */
    public function getAllByUserId(int $userId): Collection;

    /**
     * デバイストークンをID指定で削除（is_active = FALSE）
     *
     * @param int $userId
     * @param int $deviceTokenId
     * @return bool
     */
    public function deleteById(int $userId, int $deviceTokenId): bool;
}
