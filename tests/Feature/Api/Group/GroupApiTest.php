<?php

use App\Models\User;
use App\Models\Group;
use Illuminate\Support\Facades\Hash;

describe('グループ管理API', function () {
    beforeEach(function () {
        // グループマスターユーザー作成
        $this->group = Group::factory()->create([
            'name' => 'テストグループ',
        ]);
        
        $this->user = User::factory()->create([
            'group_id' => $this->group->id,
            'group_edit_flg' => true,
        ]);
        
        $this->group->update(['master_user_id' => $this->user->id]);
    });

    describe('グループ情報取得 (GET /api/groups/edit)', function () {
        it('グループ情報とメンバー一覧を取得できる', function () {
            $member = User::factory()->create([
                'group_id' => $this->group->id,
                'group_edit_flg' => false,
            ]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/groups/edit');

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'group' => [
                            'id' => $this->group->id,
                            'name' => $this->group->name,
                            'master_user_id' => $this->user->id,
                        ],
                    ],
                ])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'group' => ['id', 'name', 'master_user_id', 'created_at', 'updated_at'],
                        'members' => [
                            '*' => ['id', 'username', 'name', 'email', 'group_edit_flg', 'is_master'],
                        ],
                    ],
                ]);
        });

        it('未認証ではアクセスできない', function () {
            $response = $this->getJson('/api/groups/edit');

            $response->assertUnauthorized()
                ->assertJson([
                    
                    'message' => 'Unauthenticated.',
                ]);
        });

        it('グループ未所属の場合は404エラー', function () {
            $userWithoutGroup = User::factory()->create(['group_id' => null]);

            $response = $this->actingAs($userWithoutGroup)
                ->getJson('/api/groups/edit');

            $response->assertNotFound()
                ->assertJson([
                    
                    'message' => 'グループが見つかりません。',
                ]);
        });
    });

    describe('グループ名更新 (PATCH /api/groups)', function () {
        it('グループ名を更新できる', function () {
            $response = $this->actingAs($this->user)
                ->patchJson('/api/groups', [
                    'name' => '新しいグループ名',
                ]);

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'message' => 'グループ情報を更新しました。',
                ]);

            $this->assertDatabaseHas('groups', [
                'id' => $this->group->id,
                'name' => '新しいグループ名',
            ]);
        });

        it('空のグループ名はバリデーションエラー', function () {
            $response = $this->actingAs($this->user)
                ->patchJson('/api/groups', [
                    'name' => '',
                ]);

            $response->assertStatus(422)
                ->assertJson([
                    
                    'message' => '入力内容に誤りがあります。',
                ]);
        });
    });

    describe('メンバー追加 (POST /api/groups/members)', function () {
        it('新しいメンバーを追加できる', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/groups/members', [
                    'username' => 'newmember',
                    'email' => 'newmember@example.com',
                    'password' => 'SecureTest#9Xm2',
                    'name' => '新メンバー',
                    'group_edit_flg' => false,
                    'privacy_policy_consent' => true,
                    'terms_consent' => true,
                ]);

            $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'メンバーを追加しました。',
                ])
                ->assertJsonStructure([
                    'data' => [
                        'member' => ['id', 'username', 'name', 'email', 'group_edit_flg'],
                        'avatar_event',
                    ],
                ]);

            $this->assertDatabaseHas('users', [
                'username' => 'newmember',
                'email' => 'newmember@example.com',
                'group_id' => $this->group->id,
            ]);
        });

        it('重複するユーザー名はエラー', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/groups/members', [
                    'username' => $this->user->username,
                    'email' => 'newmail@example.com',
                    'password' => 'SecureTest#9Xm2',
                ]);

            $response->assertStatus(422)
                ->assertJson([
                    
                ]);
        });
    });

    describe('メンバー権限更新 (PATCH /api/groups/members/{member}/permission)', function () {
        it('メンバーの編集権限を更新できる', function () {
            $member = User::factory()->create([
                'group_id' => $this->group->id,
                'group_edit_flg' => false,
            ]);

            $response = $this->actingAs($this->user)
                ->patchJson("/api/groups/members/{$member->id}/permission", [
                    'group_edit_flg' => true,
                ]);

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'message' => 'メンバーの権限を更新しました。',
                ]);

            $this->assertDatabaseHas('users', [
                'id' => $member->id,
                'group_edit_flg' => true,
            ]);
        });

        it('存在しないメンバーは404エラー', function () {
            $response = $this->actingAs($this->user)
                ->patchJson('/api/groups/members/99999/permission', [
                    'group_edit_flg' => true,
                ]);

            $response->assertNotFound()
                ->assertJson([
                    
                    'message' => 'メンバーが見つかりません。',
                ]);
        });
    });

    describe('メンバーテーマ切替 (PATCH /api/groups/members/{member}/theme)', function () {
        it('自分のテーマを切り替えられる', function () {
            $this->user->update(['theme' => 'adult']);

            $response = $this->actingAs($this->user)
                ->patchJson("/api/groups/members/{$this->user->id}/theme");

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'message' => 'テーマを切り替えました。',
                    'data' => [
                        'member' => [
                            'id' => $this->user->id,
                            'theme' => 'child',
                        ],
                    ],
                ]);

            $this->assertDatabaseHas('users', [
                'id' => $this->user->id,
                'theme' => 'child',
            ]);
        });

        it('グループ編集権限があれば他メンバーのテーマを切り替えられる', function () {
            $member = User::factory()->create([
                'group_id' => $this->group->id,
                'theme' => 'adult',
            ]);

            $response = $this->actingAs($this->user)
                ->patchJson("/api/groups/members/{$member->id}/theme");

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'member' => [
                            'theme' => 'child',
                        ],
                    ],
                ]);
        });
    });

    describe('グループマスター譲渡 (POST /api/groups/transfer/{newMaster})', function () {
        it('グループマスターを譲渡できる', function () {
            $newMaster = User::factory()->create([
                'group_id' => $this->group->id,
                'group_edit_flg' => false,
            ]);

            $response = $this->actingAs($this->user)
                ->postJson("/api/groups/transfer/{$newMaster->id}");

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'message' => 'グループマスターを譲渡しました。',
                ]);

            $this->assertDatabaseHas('groups', [
                'id' => $this->group->id,
                'master_user_id' => $newMaster->id,
            ]);
        });

        it('マスターでない場合は403エラー', function () {
            $normalMember = User::factory()->create([
                'group_id' => $this->group->id,
                'group_edit_flg' => false,
            ]);

            $newMaster = User::factory()->create([
                'group_id' => $this->group->id,
            ]);

            $response = $this->actingAs($normalMember)
                ->postJson("/api/groups/transfer/{$newMaster->id}");

            $response->assertForbidden();
        });
    });

    describe('メンバー削除 (DELETE /api/groups/members/{member})', function () {
        it('メンバーをグループから削除できる', function () {
            $member = User::factory()->create([
                'group_id' => $this->group->id,
                'group_edit_flg' => false,
            ]);

            $response = $this->actingAs($this->user)
                ->deleteJson("/api/groups/members/{$member->id}");

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'message' => 'メンバーをグループから削除しました。',
                ]);

            $this->assertDatabaseHas('users', [
                'id' => $member->id,
                'group_id' => null,
                'group_edit_flg' => false,
            ]);
        });

        it('マスターは削除できない', function () {
            $response = $this->actingAs($this->user)
                ->deleteJson("/api/groups/members/{$this->user->id}");

            $response->assertForbidden();
        });
    });
});
