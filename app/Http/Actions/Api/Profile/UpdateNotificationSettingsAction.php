<?php

namespace App\Http\Actions\Api\Profile;

use App\Http\Requests\Api\Profile\UpdateNotificationSettingsRequest;
use App\Http\Responders\Api\Profile\NotificationSettingsResponder;
use App\Services\Profile\NotificationSettingsServiceInterface;
use Illuminate\Http\JsonResponse;

/**
 * 通知設定更新アクション
 * 
 * PUT /api/v1/profile/notification-settings
 */
class UpdateNotificationSettingsAction
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
     * 通知設定を更新
     *
     * @param UpdateNotificationSettingsRequest $request
     * @return JsonResponse
     */
    public function __invoke(UpdateNotificationSettingsRequest $request): JsonResponse
    {
        $user = $request->user();

        $updatedSettings = $this->service->updateSettings($user, $request->validated());

        return $this->responder->updated($updatedSettings);
    }
}
