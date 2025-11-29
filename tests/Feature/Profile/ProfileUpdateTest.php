<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * プロフィール更新機能のテスト
 * 
 * - username, email, name の更新
 * - 自己除外による重複チェック
 * - バリデーションエラー
 */
class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * プロフィール更新: 正常系（全フィールド更新）
     */
    public function test_profile_can_be_updated_with_all_fields(): void
    {
        $user = User::factory()->create([
            'username' => 'oldusername',
            'email' => 'old@example.com',
            'name' => 'Old Name',
        ]);

        $response = $this->actingAs($user)->patch('/profile/update', [
            'username' => 'newusername',
            'email' => 'new@example.com',
            'name' => 'New Name',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'profile-updated');

        $user->refresh();

        $this->assertEquals('newusername', $user->username);
        $this->assertEquals('new@example.com', $user->email);
        $this->assertEquals('New Name', $user->name);
    }

    /**
     * プロフィール更新: nameが空の場合はusernameを使用
     */
    public function test_profile_uses_username_when_name_is_empty(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $response = $this->actingAs($user)->patch('/profile/update', [
            'username' => 'newusername',
            'email' => 'test@example.com',
            'name' => '', // 空文字
        ]);

        $response->assertRedirect();

        $user->refresh();

        $this->assertEquals('newusername', $user->username);
        $this->assertEquals('newusername', $user->name); // usernameが設定される
    }

    /**
     * プロフィール更新: 自己除外（自分のemailは許可）
     */
    public function test_profile_allows_own_email(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        $response = $this->actingAs($user)->patch('/profile/update', [
            'username' => 'testuser',
            'email' => 'test@example.com', // 自分のemail
            'name' => 'Test User',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    /**
     * プロフィール更新: 自己除外（自分のusernameは許可）
     */
    public function test_profile_allows_own_username(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        $response = $this->actingAs($user)->patch('/profile/update', [
            'username' => 'testuser', // 自分のusername
            'email' => 'newemail@example.com',
            'name' => 'Test User',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    /**
     * プロフィール更新: 他ユーザーのemailでエラー
     */
    public function test_profile_rejects_duplicate_email(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        $response = $this->actingAs($user1)->patch('/profile/update', [
            'username' => 'user1',
            'email' => 'user2@example.com', // user2のemail
            'name' => 'User 1',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * プロフィール更新: 他ユーザーのusernameでエラー
     */
    public function test_profile_rejects_duplicate_username(): void
    {
        $user1 = User::factory()->create(['username' => 'user1']);
        $user2 = User::factory()->create(['username' => 'user2']);

        $response = $this->actingAs($user1)->patch('/profile/update', [
            'username' => 'user2', // user2のusername
            'email' => 'user1@example.com',
            'name' => 'User 1',
        ]);

        $response->assertSessionHasErrors('username');
    }

    /**
     * プロフィール更新: 必須フィールドが空でエラー
     */
    public function test_profile_requires_username_and_email(): void
    {
        $user = User::factory()->create();

        // usernameが空
        $response = $this->actingAs($user)->patch('/profile/update', [
            'username' => '',
            'email' => 'test@example.com',
        ]);
        $response->assertSessionHasErrors('username');

        // emailが空
        $response = $this->actingAs($user)->patch('/profile/update', [
            'username' => 'testuser',
            'email' => '',
        ]);
        $response->assertSessionHasErrors('email');
    }

    /**
     * プロフィール更新: 無効なemail形式でエラー
     */
    public function test_profile_rejects_invalid_email_format(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/profile/update', [
            'username' => 'testuser',
            'email' => 'invalid-email',
            'name' => 'Test User',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * プロフィール更新: 無効なusername形式でエラー（記号含む）
     */
    public function test_profile_rejects_invalid_username_format(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/profile/update', [
            'username' => 'user@name', // @は不可
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $response->assertSessionHasErrors('username');
    }
}
