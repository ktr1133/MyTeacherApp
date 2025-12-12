<?php

use App\Models\User;

/**
 * 通知設定API テスト
 * 
 * エンドポイント:
 * - GET  /api/profile/notification-settings
 * - PUT  /api/profile/notification-settings
 */
describe('通知設定API', function () {
    beforeEach(function () {
        $this->user = User::factory()->create([
            'notification_settings' => [
                'push_enabled' => true,
                'push_task_enabled' => true,
                'push_group_enabled' => true,
                'push_token_enabled' => true,
                'push_system_enabled' => true,
                'push_sound_enabled' => true,
                'push_vibration_enabled' => true,
            ],
        ]);
    });

    describe('GET /api/profile/notification-settings', function () {
        it('認証済みユーザーの通知設定を取得できること', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/profile/notification-settings');

            $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'push_enabled',
                        'push_task_enabled',
                        'push_group_enabled',
                        'push_token_enabled',
                        'push_system_enabled',
                        'push_sound_enabled',
                        'push_vibration_enabled',
                    ],
                ])
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
        });

        it('通知設定が未設定の場合はデフォルト値を返すこと', function () {
            // notification_settingsがnullのユーザーを作成
            $userWithoutSettings = User::factory()->create([
                'notification_settings' => null,
            ]);

            $response = $this->actingAs($userWithoutSettings)
                ->getJson('/api/profile/notification-settings');

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
        });

        it('未認証の場合は401エラーを返すこと', function () {
            $response = $this->getJson('/api/profile/notification-settings');

            $response->assertUnauthorized();
        });
    });

    describe('PUT /api/profile/notification-settings', function () {
        it('通知設定を更新できること', function () {
            $updateData = [
                'push_enabled' => false,
                'push_task_enabled' => false,
                'push_group_enabled' => false,
                'push_token_enabled' => false,
                'push_system_enabled' => false,
                'push_sound_enabled' => false,
                'push_vibration_enabled' => false,
            ];

            $response = $this->actingAs($this->user)
                ->putJson('/api/profile/notification-settings', $updateData);

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'message' => '通知設定を更新しました。',
                    'data' => $updateData,
                ]);

            // DBに保存されているか確認
            $this->user->refresh();
            expect($this->user->notification_settings)->toBe($updateData);
        });

        it('部分的な更新ができること', function () {
            // push_task_enabledのみを更新
            $updateData = [
                'push_task_enabled' => false,
            ];

            $response = $this->actingAs($this->user)
                ->putJson('/api/profile/notification-settings', $updateData);

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'message' => '通知設定を更新しました。',
                    'data' => [
                        'push_enabled' => true,        // 元の値のまま
                        'push_task_enabled' => false,  // 更新された値
                        'push_group_enabled' => true,  // 元の値のまま
                        'push_token_enabled' => true,
                        'push_system_enabled' => true,
                        'push_sound_enabled' => true,
                        'push_vibration_enabled' => true,
                    ],
                ]);

            // DBに保存されているか確認
            $this->user->refresh();
            expect($this->user->notification_settings['push_task_enabled'])->toBe(false);
            expect($this->user->notification_settings['push_enabled'])->toBe(true);
        });

        it('不正な設定キーを含む場合は422エラーを返すこと', function () {
            $invalidData = [
                'push_enabled' => true,
                'invalid_key' => true,  // 不正なキー
            ];

            $response = $this->actingAs($this->user)
                ->putJson('/api/profile/notification-settings', $invalidData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['invalid_key']);
        });

        it('boolean以外の値を含む場合は422エラーを返すこと', function () {
            $invalidData = [
                'push_enabled' => 'not_boolean',  // boolean以外
            ];

            $response = $this->actingAs($this->user)
                ->putJson('/api/profile/notification-settings', $invalidData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['push_enabled']);
        });

        it('未認証の場合は401エラーを返すこと', function () {
            $updateData = [
                'push_enabled' => false,
            ];

            $response = $this->putJson('/api/profile/notification-settings', $updateData);

            $response->assertUnauthorized();
        });
    });
});
