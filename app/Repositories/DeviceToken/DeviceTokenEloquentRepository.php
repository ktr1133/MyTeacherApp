<?php

namespace App\Repositories\DeviceToken;

use App\Models\UserDeviceToken;
use Illuminate\Support\Collection;

/**
 * FCMデバイストークンEloquentリポジトリ
 */
class DeviceTokenEloquentRepository implements DeviceTokenRepositoryInterface
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
    ): UserDeviceToken {
        return UserDeviceToken::updateOrCreate(
            ['device_token' => $deviceToken],
            [
                'user_id' => $userId,
                'device_type' => $deviceType,
                'device_name' => $deviceName,
                'app_version' => $appVersion,
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );
    }

    /**
     * デバイストークンを削除（is_active = FALSE）
     *
     * @param int $userId
     * @param string $deviceToken
     * @return bool
     */
    public function delete(int $userId, string $deviceToken): bool
    {
        return UserDeviceToken::where('user_id', $userId)
            ->where('device_token', $deviceToken)
            ->update(['is_active' => false]) > 0;
    }

    /**
     * ユーザーのアクティブなデバイストークンを取得
     *
     * @param int $userId
     * @return Collection<int, UserDeviceToken>
     */
    public function getActiveTokensByUserId(int $userId): Collection
    {
        return UserDeviceToken::where('user_id', $userId)
            ->active()
            ->orderBy('last_used_at', 'desc')
            ->get();
    }

    /**
     * デバイストークンが存在するか確認
     *
     * @param string $deviceToken
     * @return bool
     */
    public function exists(string $deviceToken): bool
    {
        return UserDeviceToken::where('device_token', $deviceToken)->exists();
    }

    /**
     * ユーザーの全デバイストークンを取得（is_activeに関わらず）
     *
     * @param int $userId
     * @return Collection<int, UserDeviceToken>
     */
    public function getAllByUserId(int $userId): Collection
    {
        return UserDeviceToken::where('user_id', $userId)
            ->orderBy('last_used_at', 'desc')
            ->get();
    }

    /**
     * デバイストークンをID指定で削除（is_active = FALSE）
     *
     * @param int $userId
     * @param int $deviceTokenId
     * @return bool
     */
    public function deleteById(int $userId, int $deviceTokenId): bool
    {
        return UserDeviceToken::where('user_id', $userId)
            ->where('id', $deviceTokenId)
            ->update(['is_active' => false]) > 0;
    }

    /**
     * デバイストークンを非アクティブ化
     *
     * @param string $deviceToken
     * @return bool
     */
    public function deactivate(string $deviceToken): bool
    {
        return UserDeviceToken::where('device_token', $deviceToken)
            ->update(['is_active' => false]) > 0;
    }
}
