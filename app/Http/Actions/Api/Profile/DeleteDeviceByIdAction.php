<?php

namespace App\Http\Actions\Api\Profile;

use App\Http\Responders\Api\Profile\DeviceResponder;
use App\Services\DeviceToken\DeviceTokenManagementServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * デバイスID指定削除アクション
 * 
 * DELETE /api/profile/fcm-token/{id}
 */
class DeleteDeviceByIdAction
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
     * デバイスをID指定で削除
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $result = $this->service->deleteDeviceById($user->id, $id);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'デバイスが見つかりません。',
            ], 404);
        }

        return $this->responder->deviceDeleted();
    }
}
