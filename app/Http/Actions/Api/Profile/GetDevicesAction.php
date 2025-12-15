<?php

namespace App\Http\Actions\Api\Profile;

use App\Http\Responders\Api\Profile\DeviceResponder;
use App\Services\DeviceToken\DeviceTokenManagementServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * デバイス一覧取得アクション
 * 
 * GET /api/profile/devices
 */
class GetDevicesAction
{
    /**
     * コンストラクタ
     *
     * @param DeviceTokenManagementServiceInterface $service
     * @param DeviceResponder $responder
     */
    public function __construct(
        protected DeviceTokenManagementServiceInterface $service,
        protected DeviceResponder $responder
    ) {}

    /**
     * ユーザーの全デバイスを取得
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $devices = $this->service->getAllDevices($user->id);

        return $this->responder->devicesList($devices);
    }
}
