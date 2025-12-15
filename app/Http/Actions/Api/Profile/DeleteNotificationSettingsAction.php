<?php

namespace App\Http\Actions\Api\Profile;

use App\Http\Responders\Api\Profile\NotificationSettingsResponder;
use App\Services\Profile\NotificationSettingsServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 通知設定削除アクション
 * 
 * DELETE /api/profile/notification-settings
 * デフォルト設定に復元する
 */
class DeleteNotificationSettingsAction
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
     * 通知設定を削除（デフォルトに復元）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // デフォルト設定で上書き
        $defaultSettings = [
            'push_enabled' => true,
            'push_task_enabled' => true,
            'push_group_enabled' => true,
            'push_token_enabled' => true,
            'push_system_enabled' => true,
            'push_sound_enabled' => true,
            'push_vibration_enabled' => true,
        ];
        
        $settings = $this->service->updateSettings($user, $defaultSettings);

        return $this->responder->reset($settings);
    }
}
