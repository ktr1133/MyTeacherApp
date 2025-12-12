<?php

use App\Jobs\SendPushNotificationJob;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\NotificationTemplate;
use App\Models\UserDeviceToken;
use App\Services\Fcm\FcmServiceInterface;
use App\Services\DeviceToken\DeviceTokenManagementServiceInterface;
use App\Services\NotificationSettings\NotificationSettingsServiceInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

describe('SendPushNotificationJob', function () {
    beforeEach(function () {
        // モックを作成
        $this->fcmService = Mockery::mock(FcmServiceInterface::class);
        $this->deviceTokenService = Mockery::mock(DeviceTokenManagementServiceInterface::class);
        $this->notificationSettingsService = Mockery::mock(NotificationSettingsServiceInterface::class);

        // モックをコンテナにバインド
        $this->app->instance(FcmServiceInterface::class, $this->fcmService);
        $this->app->instance(DeviceTokenManagementServiceInterface::class, $this->deviceTokenService);
        $this->app->instance(NotificationSettingsServiceInterface::class, $this->notificationSettingsService);

        // テストユーザー作成
        $this->user = User::factory()->create();

        // 通知テンプレート作成
        $this->template = NotificationTemplate::factory()->create([
            'sender_id' => $this->user->id,
            'type' => 'task_created',
            'title' => 'テストタスクが作成されました',
            'message' => 'タスク: テスト内容',
        ]);

        // ユーザー通知レコード作成
        $this->notification = UserNotification::factory()->create([
            'user_id' => $this->user->id,
            'notification_template_id' => $this->template->id,
            'is_read' => false,
        ]);

        // ログモック
        Log::spy();
    });

    afterEach(function () {
        Mockery::close();
    });

    it('有効なデバイストークン(is_active=TRUE, last_used_at < 30日前)にPush通知を送信する', function () {
        // 有効なデバイストークン作成（最終使用日が7日前）
        $deviceToken = UserDeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'device_token' => 'valid_fcm_token_12345',
            'device_type' => 'ios',
            'is_active' => true,
            'last_used_at' => now()->subDays(7),
        ]);

        // Push通知が有効
        $this->notificationSettingsService
            ->shouldReceive('isPushEnabled')
            ->once()
            ->with($this->user->id, 'task')
            ->andReturn(true);

        // 有効なトークンを返す
        $this->deviceTokenService
            ->shouldReceive('getActiveTokens')
            ->once()
            ->with($this->user->id)
            ->andReturn(collect([$deviceToken]));

        // ペイロード構築
        $this->fcmService
            ->shouldReceive('buildPayload')
            ->once()
            ->with(
                'テストタスクが作成されました',
                'タスク: テスト内容',
                Mockery::on(function ($data) {
                    return $data['notification_id'] == $this->notification->id
                        && $data['type'] === 'task_created'
                        && $data['category'] === 'task';
                })
            )
            ->andReturn([
                'notification' => [
                    'title' => 'テストタスクが作成されました',
                    'body' => 'タスク: テスト内容',
                ],
                'data' => [
                    'notification_id' => (string) $this->notification->id,
                    'type' => 'task_created',
                ],
            ]);

        // FCM送信成功
        $this->fcmService
            ->shouldReceive('sendToMultipleDevices')
            ->once()
            ->with(['valid_fcm_token_12345'], Mockery::any())
            ->andReturn([
                'success' => 1,
                'failed' => 0,
                'errors' => [],
            ]);

        // ジョブ実行
        $job = new SendPushNotificationJob($this->notification->id, $this->user->id);
        $job->handle(
            $this->fcmService,
            $this->deviceTokenService,
            $this->notificationSettingsService
        );

        // ログ確認
        Log::shouldHaveReceived('info')
            ->with('SendPushNotificationJob started', Mockery::subset([
                'user_notification_id' => $this->notification->id,
                'user_id' => $this->user->id,
            ]));

        Log::shouldHaveReceived('info')
            ->with('Push notification sent', Mockery::any());
    });

    it('is_active=FALSEのデバイストークンをスキップする', function () {
        // 無効なデバイストークン作成
        UserDeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'device_token' => 'inactive_token_12345',
            'device_type' => 'android',
            'is_active' => false,
            'last_used_at' => now()->subDays(1),
        ]);

        // Push通知が有効
        $this->notificationSettingsService
            ->shouldReceive('isPushEnabled')
            ->once()
            ->with($this->user->id, 'task')
            ->andReturn(true);

        // 有効なトークンがない（空Collection）
        $this->deviceTokenService
            ->shouldReceive('getActiveTokens')
            ->once()
            ->with($this->user->id)
            ->andReturn(collect([]));

        // buildPayloadは呼ばれない
        $this->fcmService
            ->shouldNotReceive('buildPayload');

        // FCM送信は呼ばれない
        $this->fcmService
            ->shouldNotReceive('sendToMultipleDevices');

        // ジョブ実行
        $job = new SendPushNotificationJob($this->notification->id, $this->user->id);
        $job->handle(
            $this->fcmService,
            $this->deviceTokenService,
            $this->notificationSettingsService
        );

        // ログ確認
        Log::shouldHaveReceived('info')
            ->with('No active device tokens found', Mockery::any());
    });

    it('last_used_atが30日以上前のデバイストークンをスキップする', function () {
        // 古いデバイストークン作成（40日前）
        UserDeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'device_token' => 'old_token_12345',
            'device_type' => 'ios',
            'is_active' => true,
            'last_used_at' => now()->subDays(40),
        ]);

        // Push通知が有効
        $this->notificationSettingsService
            ->shouldReceive('isPushEnabled')
            ->once()
            ->with($this->user->id, 'task')
            ->andReturn(true);

        // getActiveTokens は30日以内のトークンのみ返す前提
        $this->deviceTokenService
            ->shouldReceive('getActiveTokens')
            ->once()
            ->with($this->user->id)
            ->andReturn(collect([]));

        // buildPayloadは呼ばれない
        $this->fcmService
            ->shouldNotReceive('buildPayload');

        // FCM送信は呼ばれない
        $this->fcmService
            ->shouldNotReceive('sendToMultipleDevices');

        // ジョブ実行
        $job = new SendPushNotificationJob($this->notification->id, $this->user->id);
        $job->handle(
            $this->fcmService,
            $this->deviceTokenService,
            $this->notificationSettingsService
        );

        // ログ確認
        Log::shouldHaveReceived('info')
            ->with('No active device tokens found', Mockery::any());
    });

    it('カテゴリ別Push通知がOFFの場合は送信しない(push_task_enabled=FALSE)', function () {
        // 有効なデバイストークン作成
        UserDeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'device_token' => 'valid_token_12345',
            'device_type' => 'ios',
            'is_active' => true,
            'last_used_at' => now()->subDays(1),
        ]);

        // カテゴリ別Push通知がOFF
        $this->notificationSettingsService
            ->shouldReceive('isPushEnabled')
            ->once()
            ->with($this->user->id, 'task')
            ->andReturn(false);

        // getActiveTokensは呼ばれない
        $this->deviceTokenService
            ->shouldNotReceive('getActiveTokens');

        // buildPayloadは呼ばれない
        $this->fcmService
            ->shouldNotReceive('buildPayload');

        // FCM送信は呼ばれない
        $this->fcmService
            ->shouldNotReceive('sendToMultipleDevices');

        // ジョブ実行
        $job = new SendPushNotificationJob($this->notification->id, $this->user->id);
        $job->handle(
            $this->fcmService,
            $this->deviceTokenService,
            $this->notificationSettingsService
        );

        // ログ確認
        Log::shouldHaveReceived('info')
            ->with('Push notification disabled by user settings', Mockery::subset([
                'user_id' => $this->user->id,
                'category' => 'task',
            ]));
    });

    it('全体のPush通知がOFFの場合は送信しない(push_enabled=FALSE)', function () {
        // 有効なデバイストークン作成
        UserDeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'device_token' => 'valid_token_12345',
            'device_type' => 'android',
            'is_active' => true,
            'last_used_at' => now()->subDays(1),
        ]);

        // 全体のPush通知がOFF（isPushEnabledがfalseを返す）
        $this->notificationSettingsService
            ->shouldReceive('isPushEnabled')
            ->once()
            ->with($this->user->id, 'task')
            ->andReturn(false);

        // getActiveTokensは呼ばれない
        $this->deviceTokenService
            ->shouldNotReceive('getActiveTokens');

        // buildPayloadは呼ばれない
        $this->fcmService
            ->shouldNotReceive('buildPayload');

        // FCM送信は呼ばれない
        $this->fcmService
            ->shouldNotReceive('sendToMultipleDevices');

        // ジョブ実行
        $job = new SendPushNotificationJob($this->notification->id, $this->user->id);
        $job->handle(
            $this->fcmService,
            $this->deviceTokenService,
            $this->notificationSettingsService
        );

        // ログ確認
        Log::shouldHaveReceived('info')
            ->with('Push notification disabled by user settings', Mockery::any());
    });

    it('FCM API エラー時にリトライ機構が動作する', function () {
        // 有効なデバイストークン作成
        $deviceToken = UserDeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'device_token' => 'valid_token_12345',
            'device_type' => 'ios',
            'is_active' => true,
            'last_used_at' => now()->subDays(1),
        ]);

        // Push通知が有効
        $this->notificationSettingsService
            ->shouldReceive('isPushEnabled')
            ->once()
            ->with($this->user->id, 'task')
            ->andReturn(true);

        // 有効なトークンを返す
        $this->deviceTokenService
            ->shouldReceive('getActiveTokens')
            ->once()
            ->with($this->user->id)
            ->andReturn(collect([$deviceToken]));

        // ペイロード構築
        $this->fcmService
            ->shouldReceive('buildPayload')
            ->once()
            ->andReturn([
                'notification' => ['title' => 'Test', 'body' => 'Test'],
                'data' => [],
            ]);

        // FCM送信失敗（例外を投げる）
        $this->fcmService
            ->shouldReceive('sendToMultipleDevices')
            ->once()
            ->andThrow(new \RuntimeException('FCM API error: Service unavailable'));

        try {
            // ジョブ実行（例外が発生することを期待）
            $job = new SendPushNotificationJob($this->notification->id, $this->user->id);
            $job->handle(
                $this->fcmService,
                $this->deviceTokenService,
                $this->notificationSettingsService
            );
            
            // 例外が投げられない場合はテスト失敗
            throw new \Exception('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            // 例外が投げられることを確認
            expect($e->getMessage())->toContain('Service unavailable');
        }

        // エラーログ確認
        Log::shouldHaveReceived('error')
            ->with('SendPushNotificationJob failed', Mockery::on(function ($context) {
                return isset($context['error']) && str_contains($context['error'], 'Service unavailable');
            }));
    });

    it('InvalidRegistrationエラー時にデバイストークンをis_active=FALSEに更新する', function () {
        // 有効なデバイストークン作成
        $deviceToken = UserDeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'device_token' => 'invalid_fcm_token_12345',
            'device_type' => 'android',
            'is_active' => true,
            'last_used_at' => now()->subDays(1),
        ]);

        // Push通知が有効
        $this->notificationSettingsService
            ->shouldReceive('isPushEnabled')
            ->once()
            ->with($this->user->id, 'task')
            ->andReturn(true);

        // 有効なトークンを返す
        $this->deviceTokenService
            ->shouldReceive('getActiveTokens')
            ->once()
            ->with($this->user->id)
            ->andReturn(collect([$deviceToken]));

        // ペイロード構築
        $this->fcmService
            ->shouldReceive('buildPayload')
            ->once()
            ->andReturn([
                'notification' => ['title' => 'Test', 'body' => 'Test'],
                'data' => [],
            ]);

        // FCM送信失敗（InvalidRegistration）
        $this->fcmService
            ->shouldReceive('sendToMultipleDevices')
            ->once()
            ->andReturn([
                'success' => 0,
                'failed' => 1,
                'errors' => [
                    [
                        'device_token' => 'invalid_fcm_token_12345',
                        'error_code' => 'InvalidRegistration',
                    ],
                ],
            ]);

        // エラー種別判定
        $this->fcmService
            ->shouldReceive('getErrorType')
            ->once()
            ->with('InvalidRegistration')
            ->andReturn('invalid_token');

        // デバイストークンの無効化が呼ばれる
        $this->deviceTokenService
            ->shouldReceive('deactivateToken')
            ->once()
            ->with('invalid_fcm_token_12345');

        // ジョブ実行
        $job = new SendPushNotificationJob($this->notification->id, $this->user->id);
        $job->handle(
            $this->fcmService,
            $this->deviceTokenService,
            $this->notificationSettingsService
        );

        // ログ確認
        Log::shouldHaveReceived('info')
            ->with('Device token deactivated due to FCM error', Mockery::subset([
                'device_token' => 'invalid_fcm_token_12345',
            ]));
    });
});
