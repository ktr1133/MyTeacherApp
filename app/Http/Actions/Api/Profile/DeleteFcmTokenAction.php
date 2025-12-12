<?php

namespace App\Http\Actions\Api\Profile;

use App\Http\Requests\Api\Profile\DeleteFcmTokenRequest;
use App\Http\Responders\Api\Profile\FcmTokenResponder;
use App\Services\DeviceToken\DeviceTokenManagementServiceInterface;
use Illuminate\Http\JsonResponse;

/**
 * FCMトークン削除アクション
 * 
 * DELETE /api/profile/fcm-token
 */
class DeleteFcmTokenAction
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
     * FCMトークンを削除
     *
     * @param DeleteFcmTokenRequest $request
     * @return JsonResponse
     */
    public function __invoke(DeleteFcmTokenRequest $request): JsonResponse
    {
        $user = $request->user();

        $this->service->deleteToken($user->id, $request->device_token);

        return $this->responder->deleted();
    }
}
