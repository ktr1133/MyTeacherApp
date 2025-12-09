<?php

namespace App\Services\DeviceToken;

use App\Models\UserDeviceToken;
use App\Repositories\DeviceToken\DeviceTokenRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * デバイストークン管理サービス
 */
class DeviceTokenManagementService implements DeviceTokenManagementServiceInterface
{
    /**
     * @param DeviceTokenRepositoryInterface $repository
     */
    public function __construct(
        protected DeviceTokenRepositoryInterface $repository
    ) {}

    /**
     * FCMトークンを登録または更新
     *
     * @param int $userId
     * @param string $deviceToken
     * @param string $deviceType 'ios' | 'android'
     * @param string|null $deviceName
     * @param string|null $appVersion
     * @return UserDeviceToken
     * @throws ValidationException
     */
    public function registerToken(
        int $userId,
        string $deviceToken,
        string $deviceType,
        ?string $deviceName = null,
        ?string $appVersion = null
    ): UserDeviceToken {
        // デバイス種別のバリデーション
        if (!in_array($deviceType, ['ios', 'android'])) {
            throw ValidationException::withMessages([
                'device_type' => 'デバイス種別はiosまたはandroidのいずれかを指定してください。',
            ]);
        }

        try {
            $token = $this->repository->registerOrUpdate(
                $userId,
                $deviceToken,
                $deviceType,
                $deviceName,
                $appVersion
            );

            Log::info('FCMトークン登録完了', [
                'user_id' => $userId,
                'device_type' => $deviceType,
                'device_name' => $deviceName,
            ]);

            return $token;
        } catch (\Exception $e) {
            Log::error('FCMトークン登録エラー', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * FCMトークンを削除
     *
     * @param int $userId
     * @param string $deviceToken
     * @return bool
     */
    public function deleteToken(int $userId, string $deviceToken): bool
    {
        try {
            $result = $this->repository->delete($userId, $deviceToken);

            if ($result) {
                Log::info('FCMトークン削除完了', [
                    'user_id' => $userId,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('FCMトークン削除エラー', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * ユーザーのアクティブなデバイストークンを取得
     *
     * @param int $userId
     * @return Collection<int, UserDeviceToken>
     */
    public function getActiveTokens(int $userId): Collection
    {
        return $this->repository->getActiveTokensByUserId($userId);
    }

    /**
     * デバイストークンを非アクティブ化（FCMエラー時）
     *
     * @param string $deviceToken
     * @return bool
     */
    public function deactivateToken(string $deviceToken): bool
    {
        try {
            $result = $this->repository->deactivate($deviceToken);

            if ($result) {
                Log::info('デバイストークン非アクティブ化', [
                    'device_token' => substr($deviceToken, 0, 20) . '...',
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('デバイストークン非アクティブ化エラー', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
