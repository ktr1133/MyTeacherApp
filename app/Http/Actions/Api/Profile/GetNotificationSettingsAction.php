<?php

namespace App\Http\Actions\Api\Profile;

use App\Http\Responders\Api\Profile\NotificationSettingsResponder;
use App\Services\Profile\NotificationSettingsServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 通知設定取得アクション
 * 
 * GET /api/v1/profile/notification-settings
 */
class GetNotificationSettingsAction
{
    /**
     * コンストラクタ
     *
     * @param NotificationSettingsServiceInterface $service
     * @param NotificationSettingsResponder $responder
     */
    public function __construct(
        protected NotificationSettingsServiceInterface $service,
        protected NotificationSettingsResponder $responder
    ) {}

    /**
     * 通知設定を取得
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $settings = $this->service->getSettings($user);

        return $this->responder->success($settings);
    }
}
