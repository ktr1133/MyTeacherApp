<?php

namespace App\Http\Actions\Api\Notifications;

use App\Services\DeviceToken\DeviceTokenManagementServiceInterface;
use App\Services\NotificationSettings\NotificationSettingsServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * テスト通知送信アクション
 * 
 * POST /api/notifications/test
 * 統合テスト用エンドポイント
 */
class SendTestNotificationAction
{
    /**
     * コンストラクタ
     *
     * @param DeviceTokenManagementServiceInterface $deviceTokenService
     * @param NotificationSettingsServiceInterface $notificationSettingsService
     */
    public function __construct(
        protected DeviceTokenManagementServiceInterface $deviceTokenService,
        protected NotificationSettingsServiceInterface $notificationSettingsService
    ) {}

    /**
     * テスト通知を送信
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:task,group,token,system',
            'user_id' => 'required|integer',
            'message' => 'nullable|string',
        ]);

        $type = $request->input('type');
        $userId = $request->input('user_id');
        $message = $request->input('message', 'Test notification');

        // 通知設定確認
        $isEnabled = $this->notificationSettingsService->isPushEnabled($userId, $type);

        if (!$isEnabled) {
            Log::info('テスト通知スキップ（設定無効）', [
                'user_id' => $userId,
                'type' => $type,
                'reason' => "push_{$type}_enabled=false",
            ]);

            return response()->json([
                'push_sent' => false,
                'reason' => "push_{$type}_enabled=false",
                'devices_count' => 0,
                'fcm_tokens' => [],
            ]);
        }

        // アクティブなデバイストークンを取得
        $devices = $this->deviceTokenService->getActiveTokens($userId);
        $fcmTokens = $devices->pluck('device_token')->toArray();

        Log::info('テスト通知送信', [
            'user_id' => $userId,
            'type' => $type,
            'devices_count' => $devices->count(),
            'message' => $message,
        ]);

        // 実際のFCM送信は統合テストでは省略
        // 本番環境ではここでSendPushNotificationJobをディスパッチ

        return response()->json([
            'push_sent' => true,
            'devices_count' => $devices->count(),
            'fcm_tokens' => $fcmTokens,
            'message' => $message,
        ]);
    }
}
