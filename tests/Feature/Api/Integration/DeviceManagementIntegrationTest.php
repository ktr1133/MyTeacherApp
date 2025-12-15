<?php

use App\Models\User;
use App\Models\UserDeviceToken;

/**
 * デバイス管理API統合テスト
 */
describe('Device Management API Integration', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
    });

    describe('GET /api/profile/devices', function () {
        it('ユーザーの全デバイスを取得できること', function () {
            // デバイスを2つ作成
            UserDeviceToken::factory()->count(2)->create([
                'user_id' => $this->user->id,
                'is_active' => true,
            ]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/profile/devices');

            $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'device_type',
                            'device_name',
                            'app_version',
                            'is_active',
                            'fcm_token',
                            'last_used_at',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                ]);

            expect($response->json('data'))->toHaveCount(2);
        });
    });

    describe('DELETE /api/profile/fcm-token/{id}', function () {
        it('デバイスをID指定で削除できること', function () {
            $device = UserDeviceToken::factory()->create([
                'user_id' => $this->user->id,
                'is_active' => true,
            ]);

            $response = $this->actingAs($this->user)
                ->deleteJson("/api/profile/fcm-token/{$device->id}");

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'message' => 'デバイスを削除しました。',
                ]);

            // is_activeがfalseになっていることを確認
            $device->refresh();
            expect($device->is_active)->toBeFalse();
        });

        it('他のユーザーのデバイスは削除できないこと', function () {
            $otherUser = User::factory()->create();
            $device = UserDeviceToken::factory()->create([
                'user_id' => $otherUser->id,
                'is_active' => true,
            ]);

            $response = $this->actingAs($this->user)
                ->deleteJson("/api/profile/fcm-token/{$device->id}");

            $response->assertNotFound();

            // 他のユーザーのデバイスは変更されていないことを確認
            $device->refresh();
            expect($device->is_active)->toBeTrue();
        });
    });

    describe('DELETE /api/profile/notification-settings', function () {
        it('通知設定をデフォルトに戻せること', function () {
            // カスタム設定を作成
            $this->user->notification_settings = [
                'push_enabled' => false,
                'push_task_enabled' => false,
            ];
            $this->user->save();

            $response = $this->actingAs($this->user)
                ->deleteJson('/api/profile/notification-settings');

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'push_enabled' => true,
                        'push_task_enabled' => true,
                        'push_group_enabled' => true,
                        'push_token_enabled' => true,
                        'push_system_enabled' => true,
                        'push_sound_enabled' => true,
                        'push_vibration_enabled' => true,
                    ],
                ]);

            // DBが更新されていることを確認
            $this->user->refresh();
            expect($this->user->notification_settings['push_enabled'])->toBeTrue();
            expect($this->user->notification_settings['push_task_enabled'])->toBeTrue();
        });
    });

    describe('PATCH /api/profile/notification-settings', function () {
        it('PATCH メソッドで部分更新できること', function () {
            $response = $this->actingAs($this->user)
                ->patchJson('/api/profile/notification-settings', [
                    'push_task_enabled' => false,
                ]);

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'push_task_enabled' => false,
                        'push_group_enabled' => true, // 他の設定は変更されない
                    ],
                ]);
        });
    });

    describe('POST /api/notifications/test', function () {
        it('テスト通知を送信できること', function () {
            // アクティブなデバイスを作成
            UserDeviceToken::factory()->count(3)->create([
                'user_id' => $this->user->id,
                'is_active' => true,
            ]);

            $response = $this->actingAs($this->user)
                ->postJson('/api/notifications/test', [
                    'type' => 'task',
                    'user_id' => $this->user->id,
                    'message' => 'Test notification',
                ]);

            $response->assertOk()
                ->assertJson([
                    'push_sent' => true,
                    'devices_count' => 3,
                ]);

            expect($response->json('fcm_tokens'))->toHaveCount(3);
        });

        it('通知設定が無効の場合はスキップされること', function () {
            // push_task_enabled を無効化
            $this->user->notification_settings = ['push_task_enabled' => false];
            $this->user->save();

            UserDeviceToken::factory()->create([
                'user_id' => $this->user->id,
                'is_active' => true,
            ]);

            $response = $this->actingAs($this->user)
                ->postJson('/api/notifications/test', [
                    'type' => 'task',
                    'user_id' => $this->user->id,
                ]);

            $response->assertOk()
                ->assertJson([
                    'push_sent' => false,
                    'reason' => 'push_task_enabled=false',
                    'devices_count' => 0,
                ]);
        });
    });
});
