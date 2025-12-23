<?php

/**
 * 保護者招待トークン経由登録 Mobile API Integration Test
 * 
 * Phase 8: Task 4
 * 招待トークン経由でMobile APIから保護者がアカウント登録し、
 * 子アカウントとグループ作成される機能をテスト
 * 
 * @package Tests\Feature\Api\Auth
 */

use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Str;

describe('招待トークン経由の保護者登録（Mobile API）', function () {
    test('有効な招待トークンでAPI経由登録すると、Sanctumトークン・グループ・子紐付けが返る', function () {
        // 1. 子アカウント作成（13歳未満、招待トークン付き）
        $childUser = User::factory()->create([
            'email' => 'child@example.com',
            'username' => 'child_user',
            'is_minor' => true,
            'parent_email' => 'parent@example.com',
            'parent_invitation_token' => Str::random(64),
            'parent_invitation_expires_at' => now()->addDays(30),
            'parent_consented_at' => now(),
            'group_id' => null,
        ]);

        $invitationToken = $childUser->parent_invitation_token;

        // 2. Mobile APIから保護者が登録
        $response = $this->postJson('/api/auth/register', [
            'username' => 'parent_taro',
            'email' => 'parent@example.com',
            'password' => 'P@rentTest9$Xm',
            'password_confirmation' => 'P@rentTest9$Xm',
            'timezone' => 'Asia/Tokyo',
            'privacy_policy_consent' => true,
            'terms_consent' => true,
            'parent_invite_token' => $invitationToken,
        ]);

        // 3. 201 Created、トークン発行
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'token',
            'user' => ['id', 'username', 'email', 'group_id', 'group_edit_flg'],
            'linked_child' => ['id', 'username', 'group_id'],
            'group' => ['id', 'name', 'master_user_id'],
        ]);

        // 4. レスポンス検証
        $data = $response->json();
        expect($data['token'])->toBeString(); // Sanctumトークン
        expect($data['user']['username'])->toBe('parent_taro');
        expect($data['user']['group_id'])->not->toBeNull();
        expect($data['user']['group_edit_flg'])->toBeTrue();

        // 5. 子アカウント情報が返る
        expect($data['linked_child']['username'])->toBe('child_user');
        expect($data['linked_child']['group_id'])->toBe($data['user']['group_id']);

        // 6. グループ情報が返る
        expect($data['group']['name'])->toHaveLength(8); // ランダム8文字
        expect($data['group']['master_user_id'])->toBe($data['user']['id']);

        // 7. DB検証: グループ作成確認
        $parentUser = User::where('email', 'parent@example.com')->first();
        expect($parentUser->group_id)->not->toBeNull();

        $group = Group::find($parentUser->group_id);
        expect($group)->not->toBeNull();
        expect($group->master_user_id)->toBe($parentUser->id);

        // 8. DB検証: 子アカウントが同じグループに参加
        $childUser->refresh();
        expect($childUser->group_id)->toBe($group->id);
        expect($childUser->parent_invitation_token)->toBeNull(); // トークン無効化
    });

    test('期限切れ招待トークンでAPI登録すると400エラーが返る', function () {
        // 子アカウント作成（招待トークン期限切れ）
        $childUser = User::factory()->create([
            'email' => 'child@example.com',
            'is_minor' => true,
            'parent_invitation_token' => Str::random(64),
            'parent_invitation_expires_at' => now()->subDay(), // 期限切れ
            'group_id' => null,
        ]);

        $invitationToken = $childUser->parent_invitation_token;

        // API登録試行
        $response = $this->postJson('/api/auth/register', [
            'username' => 'parent_taro',
            'email' => 'parent@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'timezone' => 'Asia/Tokyo',
            'privacy_policy_consent' => true,
            'terms_consent' => true,
            'parent_invite_token' => $invitationToken,
        ]);

        // 400 Bad Request
        $response->assertStatus(400);
        $response->assertJson([
            'message' => '招待リンクが無効または期限切れです。お子様の登録から30日以内に保護者アカウントを作成してください。',
        ]);

        // 保護者アカウント未作成
        $parentUser = User::where('email', 'parent@example.com')->first();
        expect($parentUser)->toBeNull();
    });

    test('無効な招待トークンでAPI登録すると400エラーが返る', function () {
        $invalidToken = 'invalid_token_12345678901234567890123456789012345678901234567890';

        // API登録試行
        $response = $this->postJson('/api/auth/register', [
            'username' => 'parent_taro',
            'email' => 'parent@example.com',
            'password' => 'P@rentTest9$Xm',
            'password_confirmation' => 'P@rentTest9$Xm',
            'timezone' => 'Asia/Tokyo',
            'privacy_policy_consent' => true,
            'terms_consent' => true,
            'parent_invite_token' => $invalidToken,
        ]);

        // 400 Bad Request
        $response->assertStatus(400);
        $response->assertJsonPath('message', fn($message) => str_contains($message, '無効または期限切れ'));

        // 保護者アカウント未作成
        $parentUser = User::where('email', 'parent@example.com')->first();
        expect($parentUser)->toBeNull();
    });

    test('子アカウントが既にグループ所属の場合、API登録は400エラー', function () {
        // 既存グループ作成
        $existingGroup = Group::factory()->create();

        // 子アカウント作成（既にグループ所属）
        $childUser = User::factory()->create([
            'email' => 'child@example.com',
            'is_minor' => true,
            'parent_invitation_token' => Str::random(64),
            'parent_invitation_expires_at' => now()->addDays(30),
            'group_id' => $existingGroup->id, // 既にグループ所属
        ]);

        $invitationToken = $childUser->parent_invitation_token;

        // API登録試行
        $response = $this->postJson('/api/auth/register', [
            'username' => 'parent_taro',
            'email' => 'parent@example.com',
            'password' => 'P@rentTest9$Xm',
            'password_confirmation' => 'P@rentTest9$Xm',
            'timezone' => 'Asia/Tokyo',
            'privacy_policy_consent' => true,
            'terms_consent' => true,
            'parent_invite_token' => $invitationToken,
        ]);

        // 400 Bad Request
        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'お子様は既に別のグループに所属しています。',
        ]);
    });

    test('招待トークンなしの通常API登録は正常に動作する', function () {
        // 通常の登録（招待トークンなし）
        $response = $this->postJson('/api/auth/register', [
            'username' => 'normal_user',
            'email' => 'normal@example.com',
            'password' => 'P@rentTest9$Xm',
            'password_confirmation' => 'P@rentTest9$Xm',
            'timezone' => 'Asia/Tokyo',
            'privacy_policy_consent' => true,
            'terms_consent' => true,
        ]);

        // 201 Created
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'token',
            'user' => ['id', 'username', 'email'],
        ]);

        // linked_child、groupは返らない
        $response->assertJsonMissing(['linked_child', 'group']);

        // ユーザー作成確認
        $user = User::where('email', 'normal@example.com')->first();
        expect($user)->not->toBeNull();
        expect($user->group_id)->toBeNull(); // グループ未所属
    });

    test('API登録時、保護者の同意記録が正しく保存される', function () {
        // 子アカウント作成
        $childUser = User::factory()->create([
            'email' => 'child@example.com',
            'is_minor' => true,
            'parent_invitation_token' => Str::random(64),
            'parent_invitation_expires_at' => now()->addDays(30),
            'group_id' => null,
        ]);

        $invitationToken = $childUser->parent_invitation_token;

        // API登録
        $response = $this->postJson('/api/auth/register', [
            'username' => 'parent_taro',
            'email' => 'parent@example.com',
            'password' => 'P@rentTest9$Xm',
            'password_confirmation' => 'P@rentTest9$Xm',
            'timezone' => 'Asia/Tokyo',
            'privacy_policy_consent' => true,
            'terms_consent' => true,
            'parent_invite_token' => $invitationToken,
        ]);

        $response->assertStatus(201);

        // 同意記録検証
        $parentUser = User::where('email', 'parent@example.com')->first();
        expect($parentUser->privacy_policy_version)->toBe(config('legal.current_versions.privacy_policy'));
        expect($parentUser->terms_version)->toBe(config('legal.current_versions.terms_of_service'));
        expect($parentUser->privacy_policy_agreed_at)->not->toBeNull();
        expect($parentUser->terms_agreed_at)->not->toBeNull();
    });

    test('Sanctumトークンの有効期限が30日である', function () {
        // 子アカウント作成
        $childUser = User::factory()->create([
            'email' => 'child@example.com',
            'is_minor' => true,
            'parent_invitation_token' => Str::random(64),
            'parent_invitation_expires_at' => now()->addDays(30),
            'group_id' => null,
        ]);

        $invitationToken = $childUser->parent_invitation_token;

        // API登録
        $response = $this->postJson('/api/auth/register', [
            'username' => 'parent_taro',
            'email' => 'parent@example.com',
            'password' => 'P@rentTest9$Xm',
            'password_confirmation' => 'P@rentTest9$Xm',
            'timezone' => 'Asia/Tokyo',
            'privacy_policy_consent' => true,
            'terms_consent' => true,
            'parent_invite_token' => $invitationToken,
        ]);

        $response->assertStatus(201);

        // トークン有効期限検証
        $parentUser = User::where('email', 'parent@example.com')->first();
        $token = $parentUser->tokens()->first();
        
        expect($token)->not->toBeNull();
        expect($token->name)->toBe('mobile-app');
        
        // 有効期限が約30日後であることを確認（±1分の誤差許容）
        $expectedExpiry = now()->addDays(30);
        expect($token->expires_at->timestamp)->toBeGreaterThanOrEqual($expectedExpiry->subMinute()->timestamp);
        expect($token->expires_at->timestamp)->toBeLessThanOrEqual($expectedExpiry->addMinute()->timestamp);
    });

    test('複数の保護者が同じ招待トークンを使用できない（API版）', function () {
        // 子アカウント作成
        $childUser = User::factory()->create([
            'email' => 'child@example.com',
            'is_minor' => true,
            'parent_invitation_token' => Str::random(64),
            'parent_invitation_expires_at' => now()->addDays(30),
            'group_id' => null,
        ]);

        $invitationToken = $childUser->parent_invitation_token;

        // 1人目の保護者が登録（成功）
        $this->postJson('/api/auth/register', [
            'username' => 'parent_one',
            'email' => 'parent1@example.com',
            'password' => 'P@rentTest9$Xm',
            'password_confirmation' => 'P@rentTest9$Xm',
            'timezone' => 'Asia/Tokyo',
            'privacy_policy_consent' => true,
            'terms_consent' => true,
            'parent_invite_token' => $invitationToken,
        ])->assertStatus(201);

        // 2人目の保護者が同じトークンで登録しようとする（失敗）
        $response = $this->postJson('/api/auth/register', [
            'username' => 'parent_two',
            'email' => 'parent2@example.com',
            'password' => 'P@rentTest9$Xm',
            'password_confirmation' => 'P@rentTest9$Xm',
            'timezone' => 'Asia/Tokyo',
            'privacy_policy_consent' => true,
            'terms_consent' => true,
            'parent_invite_token' => $invitationToken,
        ]);

        // 400 Bad Request（トークンが無効化されている）
        $response->assertStatus(400);

        // 2人目のアカウント未作成
        $secondParent = User::where('email', 'parent2@example.com')->first();
        expect($secondParent)->toBeNull();
    });
});
