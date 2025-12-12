<?php

namespace App\Http\Actions\Api\Profile;

use App\Http\Requests\Api\Profile\RegisterFcmTokenRequest;
use App\Http\Responders\Api\Profile\FcmTokenResponder;
use App\Models\UserDeviceToken;
use App\Services\DeviceToken\DeviceTokenManagementServiceInterface;
use Illuminate\Http\JsonResponse;

/**
 * FCMトークン登録アクション
 * 
 * POST /api/profile/fcm-token
 */
class RegisterFcmTokenAction
{
    /**
     * コンストラクタ
     *
     * @param DeviceTokenManagementServiceInterface $service
     * @param FcmTokenResponder $responder
     */
    public function __construct(
        protected DeviceTokenManagementServiceInterface $service,
        protected FcmTokenResponder $responder
    ) {}

    /**
     * FCMトークンを登録
     *
     * @param RegisterFcmTokenRequest $request
     * @return JsonResponse
     */
    public function __invoke(RegisterFcmTokenRequest $request): JsonResponse
    {
        $user = $request->user();

        try {
            // 既存トークン確認（新規 or 更新判定用）
            $existingToken = UserDeviceToken::where('device_token', $request->device_token)
                ->where('user_id', $user->id)
                ->exists();

            $token = $this->service->registerToken(
                $user->id,
                $request->device_token,
                $request->device_type,
                $request->device_name,
                $request->app_version
            );

            return $this->responder->registered($token, !$existingToken);
        } catch (\RuntimeException $e) {
            // トークン重複エラー
            return $this->responder->conflict();
        }
    }
}
