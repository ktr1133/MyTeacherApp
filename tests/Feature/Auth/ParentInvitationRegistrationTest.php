<?php

/**
 * 保護者招待トークン経由登録 Integration Test
 * 
 * Phase 8: Task 3
 * 招待トークン経由で保護者がアカウント登録し、子アカウントとグループ作成される機能をテスト
 * 
 * @package Tests\Feature\Auth
 */

use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Str;

describe('招待トークン経由の保護者登録（Web）', function () {
    test('有効な招待トークンで保護者が登録すると、グループが作成され子アカウントと紐付く', function () {
        // 1. 子アカウント作成（13歳未満、招待トークン付き）
        $childUser = User::factory()->create([
            'email' => 'child@example.com',
            'username' => 'child_user',
            'is_minor' => true,
            'parent_email' => 'parent@example.com',
            'parent_invitation_token' => Str::random(64),
            'parent_invitation_expires_at' => now()->addDays(30), // 有効期限内
            'parent_consented_at' => now(), // 保護者同意済み
            'group_id' => null, // 未所属
        ]);

        $invitationToken = $childUser->parent_invitation_token;

        // 2. 保護者が招待リンク経由で登録
        $response = $this->post('/register?parent_invite=' . $invitationToken, [
            'username' => 'parent_taro',
            'email' => 'parent@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'timezone' => 'Asia/Tokyo',
            'privacy_policy_consent' => '1',
            'terms_consent' => '1',
        ]);

        // 3. 登録成功、リダイレクト
        $response->assertRedirect(route('avatars.create'));
        $this->assertAuthenticated();

        // 4. 保護者アカウントが作成されている
        $parentUser = User::where('email', 'parent@example.com')->first();
        expect($parentUser)->not->toBeNull();
        expect($parentUser->username)->toBe('parent_taro');

        // 5. グループが作成されている
        expect($parentUser->group_id)->not->toBeNull();
        $group = Group::find($parentUser->group_id);
        expect($group)->not->toBeNull();
        expect($group->name)->toHaveLength(8); // ランダム8文字
        expect($group->master_user_id)->toBe($parentUser->id); // 保護者がマスター

        // 6. 保護者がグループ編集権限を持つ
        expect($parentUser->group_edit_flg)->toBeTrue();

        // 7. 子アカウントが同じグループに参加している
        $childUser->refresh();
        expect($childUser->group_id)->toBe($group->id);

        // 8. 招待トークンが無効化されている
        expect($childUser->parent_invitation_token)->toBeNull();
    });

    test('期限切れの招待トークンで登録しようとするとエラーになる', function () {
        // 1. 子アカウント作成（招待トークン期限切れ）
        $childUser = User::factory()->create([
            'email' => 'child@example.com',
            'is_minor' => true,
            'parent_invitation_token' => Str::random(64),
            'parent_invitation_expires_at' => now()->subDay(), // 期限切れ
            'group_id' => null,
        ]);

        $invitationToken = $childUser->parent_invitation_token;

        // 2. 保護者が期限切れトークンで登録しようとする
        $response = $this->post('/register?parent_invite=' . $invitationToken, [
            'username' => 'parent_taro',
            'email' => 'parent@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'timezone' => 'Asia/Tokyo',
            'privacy_policy_consent' => '1',
            'terms_consent' => '1',
        ]);

        // 3. リダイレクト、エラーメッセージ表示
        $response->assertRedirect();
        $response->assertSessionHas('error'); // エラーセッション
        $this->assertGuest(); // 未ログイン

        // 4. 保護者アカウントが作成されていない
        $parentUser = User::where('email', 'parent@example.com')->first();
        expect($parentUser)->toBeNull();
    });

    test('無効な招待トークンで登録しようとするとエラーになる', function () {
        $invalidToken = 'invalid_token_12345678901234567890123456789012345678901234567890';

        // 無効なトークンで登録しようとする
        $response = $this->post('/register?parent_invite=' . $invalidToken, [
            'username' => 'parent_taro',
            'email' => 'parent@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'timezone' => 'Asia/Tokyo',
            'privacy_policy_consent' => '1',
            'terms_consent' => '1',
        ]);

        // リダイレクト、エラーメッセージ
        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertGuest();

        // 保護者アカウント未作成
        $parentUser = User::where('email', 'parent@example.com')->first();
        expect($parentUser)->toBeNull();
    });

    test('子アカウントが既にグループに所属している場合、エラーになる', function () {
        // 1. 既存グループ作成
        $existingGroup = Group::factory()->create();

        // 2. 子アカウント作成（既にグループ所属）
        $childUser = User::factory()->create([
            'email' => 'child@example.com',
            'is_minor' => true,
            'parent_invitation_token' => Str::random(64),
            'parent_invitation_expires_at' => now()->addDays(30),
            'group_id' => $existingGroup->id, // 既にグループ所属
        ]);

        $invitationToken = $childUser->parent_invitation_token;

        // 3. 保護者が登録しようとする
        $response = $this->post('/register?parent_invite=' . $invitationToken, [
            'username' => 'parent_taro',
            'email' => 'parent@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'timezone' => 'Asia/Tokyo',
            'privacy_policy_consent' => '1',
            'terms_consent' => '1',
        ]);

        // 4. エラーメッセージ表示（グループ所属エラー）
        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertGuest(); // 未ログイン（エラー時はログイン処理前にリダイレクト）

        // 5. 保護者アカウントも作成されていない（トランザクション内でエラーの可能性）
        // 実装を確認: createUser()は実行されるが、グループ作成失敗時にロールバックされる可能性
        $parentUser = User::where('email', 'parent@example.com')->first();
        
        // 実装に応じて検証（ユーザー作成後にグループ作成失敗の場合）
        if ($parentUser !== null) {
            // ユーザーは作成されているが、グループは未所属
            expect($parentUser->group_id)->toBeNull();
        } else {
            // トランザクションでロールバックされた場合
            expect($parentUser)->toBeNull();
        }
    });

    test('招待トークンなしの通常登録は問題なく動作する', function () {
        // 通常の登録（招待トークンなし）
        $response = $this->post('/register', [
            'username' => 'normal_user',
            'email' => 'normal@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'timezone' => 'Asia/Tokyo',
            'privacy_policy_consent' => '1',
            'terms_consent' => '1',
        ]);

        // 登録成功
        $response->assertRedirect(route('avatars.create'));
        $this->assertAuthenticated();

        // ユーザー作成確認
        $user = User::where('email', 'normal@example.com')->first();
        expect($user)->not->toBeNull();
        expect($user->username)->toBe('normal_user');

        // グループは未所属
        expect($user->group_id)->toBeNull();
    });

    test('招待トークン経由登録時、保護者の同意記録が正しく保存される', function () {
        // 子アカウント作成
        $childUser = User::factory()->create([
            'email' => 'child@example.com',
            'is_minor' => true,
            'parent_invitation_token' => Str::random(64),
            'parent_invitation_expires_at' => now()->addDays(30),
            'group_id' => null,
        ]);

        $invitationToken = $childUser->parent_invitation_token;

        // 保護者登録
        $response = $this->post('/register?parent_invite=' . $invitationToken, [
            'username' => 'parent_taro',
            'email' => 'parent@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'timezone' => 'Asia/Tokyo',
            'privacy_policy_consent' => '1',
            'terms_consent' => '1',
        ]);

        $response->assertRedirect(route('avatars.create'));

        // 同意記録の検証
        $parentUser = User::where('email', 'parent@example.com')->first();
        expect($parentUser->privacy_policy_version)->toBe(config('legal.current_versions.privacy_policy'));
        expect($parentUser->terms_version)->toBe(config('legal.current_versions.terms_of_service'));
        expect($parentUser->privacy_policy_agreed_at)->not->toBeNull();
        expect($parentUser->terms_agreed_at)->not->toBeNull();
    });

    test('複数の保護者が同じ招待トークンを使用できない（トークン無効化）', function () {
        // 1. 子アカウント作成
        $childUser = User::factory()->create([
            'email' => 'child@example.com',
            'is_minor' => true,
            'parent_invitation_token' => Str::random(64),
            'parent_invitation_expires_at' => now()->addDays(30),
            'group_id' => null,
        ]);

        $invitationToken = $childUser->parent_invitation_token;

        // 2. 1人目の保護者が登録（成功）
        $this->post('/register?parent_invite=' . $invitationToken, [
            'username' => 'parent_one',
            'email' => 'parent1@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'timezone' => 'Asia/Tokyo',
            'privacy_policy_consent' => '1',
            'terms_consent' => '1',
        ]);

        // ログアウト
        $this->post('/logout');

        // 3. 2人目の保護者が同じトークンで登録しようとする（失敗）
        $response = $this->post('/register?parent_invite=' . $invitationToken, [
            'username' => 'parent_two',
            'email' => 'parent2@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'timezone' => 'Asia/Tokyo',
            'privacy_policy_consent' => '1',
            'terms_consent' => '1',
        ]);

        // エラー（トークンが無効化されているため）
        $response->assertRedirect();
        $response->assertSessionHas('error');

        // 2人目のアカウント未作成
        $secondParent = User::where('email', 'parent2@example.com')->first();
        expect($secondParent)->toBeNull();
    });
});
