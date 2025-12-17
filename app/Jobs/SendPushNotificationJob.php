<?php

namespace App\Jobs;

use App\Models\UserNotification;
use App\Models\User;
use App\Services\Fcm\FcmServiceInterface;
use App\Services\DeviceToken\DeviceTokenManagementServiceInterface;
use App\Services\NotificationSettings\NotificationSettingsServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Push通知送信ジョブ
 * 
 * user_notifications テーブルに登録された通知を
 * Firebase Cloud Messaging (FCM) 経由でユーザーのデバイスに送信。
 * 
 * **処理フロー**:
 * 1. 通知データ取得（user_notifications + notification_templates）
 * 2. ユーザーの通知設定確認（category別ON/OFF）
 * 3. アクティブなデバイストークン取得（is_active=TRUE、30日以内使用）
 * 4. FCM送信実行
 * 5. エラーハンドリング（invalid_token → is_active=FALSE更新）
 * 
 * @package App\Jobs
 */
class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * リトライ設定
     */
    public int $tries = 3;              // 最大3回リトライ
    public array $backoff = [60, 300];  // 1分、5分後にリトライ

    /**
     * 通知IDとユーザーID（シリアライズ可能）
     */
    protected int $userNotificationId;
    protected int $userId;

    /**
     * コンストラクタ
     *
     * @param int $userNotificationId ユーザー通知ID
     * @param int $userId ユーザーID
     */
    public function __construct(int $userNotificationId, int $userId)
    {
        $this->userNotificationId = $userNotificationId;
        $this->userId = $userId;
    }

    /**
     * ユーザーIDを取得（テスト用）
     *
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * ジョブ実行
     *
     * @param FcmServiceInterface $fcmService FCMサービス
     * @param DeviceTokenManagementServiceInterface $deviceTokenService デバイストークン管理サービス
     * @param NotificationSettingsServiceInterface $notificationSettingsService 通知設定サービス
     * @return void
     */
    public function handle(
        FcmServiceInterface $fcmService,
        DeviceTokenManagementServiceInterface $deviceTokenService,
        NotificationSettingsServiceInterface $notificationSettingsService
    ): void {
        try {
            // 1. 通知データ取得
            $userNotification = UserNotification::with(['template', 'user'])
                ->find($this->userNotificationId);

            if (!$userNotification) {
                Log::warning('UserNotification not found', [
                    'user_notification_id' => $this->userNotificationId,
                ]);
                return;
            }

            $template = $userNotification->template;
            $user = $userNotification->user;

            Log::info('SendPushNotificationJob started', [
                'user_notification_id' => $this->userNotificationId,
                'user_id' => $this->userId,
                'notification_type' => $template->type,
            ]);

            // 2. 通知設定確認（Push通知が有効か）
            $category = $this->getNotificationCategory($template->type);
            
            if (!$notificationSettingsService->isPushEnabled($this->userId, $category)) {
                Log::info('Push notification disabled by user settings', [
                    'user_id' => $this->userId,
                    'category' => $category,
                    'notification_type' => $template->type,
                ]);
                return;
            }

            // 3. アクティブなデバイストークン取得
            $deviceTokensCollection = $deviceTokenService->getActiveTokens($this->userId);
            $deviceTokens = $deviceTokensCollection->pluck('device_token')->toArray();

            if (empty($deviceTokens)) {
                Log::info('No active device tokens found', [
                    'user_id' => $this->userId,
                ]);
                return;
            }

            // 4. ペイロード構築
            $payload = $fcmService->buildPayload(
                $template->title,
                $template->message,
                [
                    'notification_id' => (string) $this->userNotificationId,
                    'type' => $template->type,
                    'category' => $category,
                    'priority' => (string) $template->priority,
                    'action_url' => $template->action_url ?? '',
                    'created_at' => $userNotification->created_at->toIso8601String(),
                ]
            );

            // 5. FCM送信実行
            $results = $fcmService->sendToMultipleDevices($deviceTokens, $payload);

            Log::info('Push notification sent', [
                'user_id' => $this->userId,
                'device_count' => count($deviceTokens),
                'success' => $results['success'],
                'failed' => $results['failed'],
            ]);

            // 6. エラーハンドリング（invalid_token → トークン無効化）
            if (!empty($results['errors'])) {
                foreach ($results['errors'] as $error) {
                    $errorType = $fcmService->getErrorType($error['error_code']);
                    
                    if ($errorType === 'invalid_token') {
                        // デバイストークンを無効化（device_tokenから取得）
                        $fullToken = $this->findFullToken($deviceTokens, $error['device_token']);
                        
                        if ($fullToken) {
                            $deviceTokenService->deactivateToken($fullToken);
                            
                            Log::info('Device token deactivated due to FCM error', [
                                'device_token' => $error['device_token'],
                                'error_code' => $error['error_code'],
                            ]);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('SendPushNotificationJob failed', [
                'user_notification_id' => $this->userNotificationId,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // リトライのため再スロー
        }
    }

    /**
     * ジョブ失敗時の処理
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendPushNotificationJob failed permanently', [
            'user_notification_id' => $this->userNotificationId,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * 通知種別からカテゴリを判定
     *
     * @param string $notificationType notification_templates.type
     * @return string カテゴリ（task, group, token, system）
     */
    private function getNotificationCategory(string $notificationType): string
    {
        // タスク関連
        if (str_starts_with($notificationType, 'task_')) {
            return 'task';
        }

        // グループ関連
        if (str_starts_with($notificationType, 'group_')) {
            return 'group';
        }

        // トークン関連
        if (str_starts_with($notificationType, 'token_')) {
            return 'token';
        }

        // 親子紐付け関連（Phase 5-2拡張）
        if (str_starts_with($notificationType, 'parent_link_')) {
            return 'group'; // グループ関連機能のため 'group' カテゴリに分類
        }

        // システム関連
        if (str_starts_with($notificationType, 'system_')) {
            return 'system';
        }

        // デフォルトはシステム
        return 'system';
    }

    /**
     * 省略されたトークン文字列から完全なトークンを検索
     *
     * @param array $deviceTokens 完全なトークン配列
     * @param string $abbreviatedToken 省略されたトークン（例: "eXwZ1234..."）
     * @return string|null 完全なトークン、見つからない場合はnull
     */
    private function findFullToken(array $deviceTokens, string $abbreviatedToken): ?string
    {
        $prefix = str_replace('...', '', $abbreviatedToken);
        
        foreach ($deviceTokens as $token) {
            if (str_starts_with($token, $prefix)) {
                return $token;
            }
        }

        return null;
    }
}
