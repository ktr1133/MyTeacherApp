<?php

use App\Models\User;
use App\Models\UserDeviceToken;

/**
 * FCMトークン管理API テスト
 * 
 * エンドポイント:
 * - POST   /api/profile/fcm-token
 * - DELETE /api/profile/fcm-token
 */
describe('FCMトークン管理API', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
    });

    describe('POST /api/profile/fcm-token', function () {
        it('FCMトークンを登録できること', function () {
            $tokenData = [
                'device_token' => 'test_fcm_token_12345',
                'device_type' => 'ios',
                'device_name' => 'iPhone 15 Pro',
                'app_version' => '1.0.0',
            ];

            $response = $this->actingAs($this->user)
                ->postJson('/api/profile/fcm-token', $tokenData);

            $response->assertCreated()
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'user_id',
                        'device_token',
                        'device_type',
                        'device_name',
                        'app_version',
                        'is_active',
                        'last_used_at',
                        'created_at',
                        'updated_at',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'user_id' => $this->user->id,
                        'device_token' => 'test_fcm_token_12345',
                        'device_type' => 'ios',
                        'device_name' => 'iPhone 15 Pro',
                        'app_version' => '1.0.0',
                        'is_active' => true,
                    ],
                ]);

            // DBに保存されているか確認
            $this->assertDatabaseHas('user_device_tokens', [
                'user_id' => $this->user->id,
                'device_token' => 'test_fcm_token_12345',
                'device_type' => 'ios',
                'is_active' => true,
            ]);
        });

        it('同じトークンを再登録した場合はlast_used_atが更新されること', function () {
            // 初回登録
            $token = UserDeviceToken::factory()->create([
                'user_id' => $this->user->id,
                'device_token' => 'test_fcm_token_existing',
                'device_type' => 'android',
                'last_used_at' => now()->subDays(7), // 7日前
            ]);

            $initialLastUsedAt = $token->last_used_at;

            // 同じトークンで再登録
            $this->travel(1)->days();

            $response = $this->actingAs($this->user)
                ->postJson('/api/profile/fcm-token', [
                    'device_token' => 'test_fcm_token_existing',
                    'device_type' => 'android',
                    'device_name' => 'Pixel 8',
                    'app_version' => '1.1.0',
                ]);

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'device_token' => 'test_fcm_token_existing',
                    ],
                ]);

            // last_used_atが更新されているか確認
            $token->refresh();
            expect($token->last_used_at)->not->toBe($initialLastUsedAt);
            expect($token->last_used_at->greaterThan($initialLastUsedAt))->toBeTrue();
        });

        it('異なるユーザーが同じトークンを登録しようとすると409エラーを返すこと', function () {
            // 既存ユーザーがトークン登録
            UserDeviceToken::factory()->create([
                'user_id' => $this->user->id,
                'device_token' => 'test_fcm_token_conflict',
                'device_type' => 'ios',
            ]);

            // 別ユーザーが同じトークンを登録しようとする
            $otherUser = User::factory()->create();

            $response = $this->actingAs($otherUser)
                ->postJson('/api/profile/fcm-token', [
                    'device_token' => 'test_fcm_token_conflict',
                    'device_type' => 'ios',
                    'device_name' => 'iPad Pro',
                    'app_version' => '1.0.0',
                ]);

            $response->assertStatus(409)
                ->assertJson([
                    'success' => false,
                    'message' => 'このデバイストークンは既に別のユーザーに登録されています。',
                ]);
        });

        it('device_typeが不正な値の場合は422エラーを返すこと', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/profile/fcm-token', [
                    'device_token' => 'test_fcm_token_invalid_type',
                    'device_type' => 'windows',  // 不正な値（ios/android以外）
                    'device_name' => 'PC',
                    'app_version' => '1.0.0',
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['device_type']);
        });

        it('未認証の場合は401エラーを返すこと', function () {
            $response = $this->postJson('/api/profile/fcm-token', [
                'device_token' => 'test_fcm_token_unauth',
                'device_type' => 'ios',
                'device_name' => 'iPhone',
                'app_version' => '1.0.0',
            ]);

            $response->assertUnauthorized();
        });
    });

    describe('DELETE /api/profile/fcm-token', function () {
        it('FCMトークンを削除できること（is_active=FALSEに更新）', function () {
            // トークンを事前に登録
            $token = UserDeviceToken::factory()->create([
                'user_id' => $this->user->id,
                'device_token' => 'test_fcm_token_to_delete',
                'device_type' => 'ios',
                'is_active' => true,
            ]);

            $response = $this->actingAs($this->user)
                ->deleteJson('/api/profile/fcm-token', [
                    'device_token' => 'test_fcm_token_to_delete',
                ]);

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'message' => 'FCMトークンを削除しました。',
                ]);

            // is_activeがFALSEに更新されているか確認
            $token->refresh();
            expect($token->is_active)->toBe(false);
        });

        it('未認証の場合は401エラーを返すこと', function () {
            $response = $this->deleteJson('/api/profile/fcm-token', [
                'device_token' => 'test_fcm_token_unauth',
            ]);

            $response->assertUnauthorized();
        });
    });
});
