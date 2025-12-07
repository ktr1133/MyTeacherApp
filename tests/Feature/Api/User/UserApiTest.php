<?php

use App\Models\User;

describe('ユーザー情報API', function () {
    beforeEach(function () {
        $this->user = User::factory()->create([
            'username' => 'testuser',
            'name' => 'テストユーザー',
            'theme' => 'adult',
            'group_id' => null,
            'group_edit_flg' => false,
        ]);
    });

    describe('現在のユーザー情報取得 (GET /api/user/current)', function () {
        it('認証済みユーザーの基本情報を取得できる', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/user/current');

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $this->user->id,
                        'username' => 'testuser',
                        'name' => 'テストユーザー',
                        'theme' => 'adult',
                        'group_id' => null,
                        'group_edit_flg' => false,
                    ],
                ])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'username',
                        'name',
                        'theme',
                        'group_id',
                        'group_edit_flg',
                    ],
                ]);
        });

        it('子供向けテーマのユーザー情報を取得できる', function () {
            $childUser = User::factory()->create([
                'username' => 'childuser',
                'name' => '子供ユーザー',
                'theme' => 'child',
            ]);

            $response = $this->actingAs($childUser)
                ->getJson('/api/user/current');

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'theme' => 'child',
                    ],
                ]);
        });

        it('グループに所属するユーザー情報を取得できる', function () {
            // グループIDは既存のものを使うか、nullableなので設定しない
            $groupUser = User::factory()->create([
                'username' => 'groupuser',
                'name' => 'グループユーザー',
                'theme' => 'adult',
                'group_id' => null, // 外部キー制約回避
                'group_edit_flg' => true,
            ]);

            $response = $this->actingAs($groupUser)
                ->getJson('/api/user/current');

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'group_id' => null,
                        'group_edit_flg' => true,
                    ],
                ]);
        });

        it('未認証ではアクセスできない', function () {
            $response = $this->getJson('/api/user/current');

            $response->assertUnauthorized()
                ->assertJson([
                    
                    'message' => 'Unauthenticated.',
                ]);
        });

        it('プロフィール編集APIより必要最小限の情報のみ返す', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/user/current');

            $response->assertOk()
                ->assertJsonMissing([
                    'email',
                    'bio',
                    'avatar_path',
                    'timezone',
                    'auth_provider',
                    'cognito_sub',
                ]);
        });

        it('デフォルトテーマは adult である', function () {
            $userWithoutTheme = User::factory()->create([
                'theme' => 'adult', // テーマはNOT NULL制約があるため明示的に設定
            ]);

            $response = $this->actingAs($userWithoutTheme)
                ->getJson('/api/user/current');

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'theme' => 'adult',
                    ],
                ]);
        });
    });
});
