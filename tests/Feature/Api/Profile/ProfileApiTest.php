<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

describe('プロフィール管理API', function () {
    beforeEach(function () {
        $this->user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'name' => 'Test User',
            'timezone' => 'Asia/Tokyo',
        ]);
    });

    describe('プロフィール取得 (GET /api/profile/edit)', function () {
        it('プロフィール情報を取得できる', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/profile/edit');

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $this->user->id,
                        'username' => 'testuser',
                        'name' => 'Test User',
                        'email' => 'test@example.com',
                        'timezone' => 'Asia/Tokyo',
                        'theme' => 'light',
                    ],
                ])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id', 'username', 'name', 'email', 'avatar_path', 'bio',
                        'timezone', 'theme', 'group_id', 'group_edit_flg',
                        'auth_provider', 'cognito_sub', 'created_at', 'updated_at',
                    ],
                ]);
        });

        it('未認証ではアクセスできない', function () {
            $response = $this->getJson('/api/profile/edit');

            $response->assertUnauthorized()
                ->assertJson([
                    
                    'message' => 'Unauthenticated.',
                ]);
        });
    });

    describe('プロフィール更新 (PATCH /api/profile)', function () {
        it('プロフィール情報を更新できる', function () {
            $response = $this->actingAs($this->user)
                ->patchJson('/api/profile', [
                    'username' => 'newusername',
                    'name' => 'New Name',
                    'email' => 'newemail@example.com',
                ]);

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'message' => 'プロフィールを更新しました。',
                    'data' => [
                        'username' => 'newusername',
                        'name' => 'New Name',
                        'email' => 'newemail@example.com',
                    ],
                ]);

            $this->assertDatabaseHas('users', [
                'id' => $this->user->id,
                'username' => 'newusername',
                'name' => 'New Name',
                'email' => 'newemail@example.com',
            ]);
        });

        it('重複するユーザー名はバリデーションエラー', function () {
            $otherUser = User::factory()->create(['username' => 'existinguser']);

            $response = $this->actingAs($this->user)
                ->patchJson('/api/profile', [
                    'username' => 'existinguser',
                ]);

            $response->assertStatus(422)
                ->assertJson([
                    
                    'message' => '入力内容に誤りがあります。',
                ]);
        });

        it('nameが空の場合はusernameを使用する', function () {
            $response = $this->actingAs($this->user)
                ->patchJson('/api/profile', [
                    'username' => 'newuser',
                    'name' => null, // 空文字列はバリデーションエラーになるのでnullを使用
                ]);

            $response->assertOk();

            $this->user->refresh();
            $this->assertEquals('newuser', $this->user->name);
        });
    });

    describe('アカウント削除 (DELETE /api/profile)', function () {
        it('通常ユーザーのアカウントを削除できる', function () {
            $response = $this->actingAs($this->user)
                ->deleteJson('/api/profile', [
                    'password' => 'password', // Factory default
                ]);

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'message' => 'アカウントを削除しました。',
                ]);

            // SoftDeletesを使用しているため論理削除を確認
            $this->assertSoftDeleted('users', [
                'id' => $this->user->id,
            ]);
        });

        it('パスワード確認なしでは削除できない', function () {
            $response = $this->actingAs($this->user)
                ->deleteJson('/api/profile', []);

            $response->assertStatus(422)
                ->assertJson([
                    
                ]);
        });

        it('Cognito認証の場合はパスワード不要', function () {
            $this->user->update(['auth_provider' => 'cognito']);

            $response = $this->actingAs($this->user)
                ->deleteJson('/api/profile', [
                    'password' => 'dummy', // Cognito認証なのでチェックされない
                ]);

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                ]);
        });
    });

    describe('タイムゾーン設定取得 (GET /api/profile/timezone)', function () {
        it('現在のタイムゾーン設定と選択肢を取得できる', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/profile/timezone');

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'current_timezone' => 'Asia/Tokyo',
                    ],
                ])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'current_timezone',
                        'current_timezone_name',
                        'timezones_grouped',
                    ],
                ]);
        });
    });

    describe('タイムゾーン更新 (PUT /api/profile/timezone)', function () {
        it('タイムゾーンを更新できる', function () {
            $response = $this->actingAs($this->user)
                ->putJson('/api/profile/timezone', [
                    'timezone' => 'America/New_York',
                ]);

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'message' => 'タイムゾーンを更新しました。',
                    'data' => [
                        'timezone' => 'America/New_York',
                    ],
                ]);

            $this->assertDatabaseHas('users', [
                'id' => $this->user->id,
                'timezone' => 'America/New_York',
            ]);
        });

        it('不正なタイムゾーンはバリデーションエラー', function () {
            $response = $this->actingAs($this->user)
                ->putJson('/api/profile/timezone', [
                    'timezone' => 'Invalid/Timezone',
                ]);

            $response->assertStatus(422)
                ->assertJson([
                    
                    'message' => '入力内容に誤りがあります。',
                ]);
        });
    });
});
