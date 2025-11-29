<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 有効なメールアドレスでバリデーション成功する
     */
    public function test_valid_email_passes_validation(): void
    {
        $response = $this->postJson('/validate/email', [
            'email' => 'newuser@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
            ]);
    }

    /**
     * 既に使用されているメールアドレスでバリデーション失敗する
     */
    public function test_duplicate_email_fails_validation(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->postJson('/validate/email', [
            'email' => 'existing@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => false,
                'message' => 'このメールアドレスは既に使用されています。',
            ]);
    }

    /**
     * 除外ユーザーIDを指定すると自分のメールアドレスは許可される
     */
    public function test_own_email_is_allowed_with_exclude_user_id(): void
    {
        $user = User::factory()->create([
            'email' => 'myemail@example.com',
        ]);

        $response = $this->postJson('/validate/email', [
            'email' => 'myemail@example.com',
            'exclude_user_id' => $user->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
            ]);
    }

    /**
     * 除外ユーザーID指定時も他人の重複メールは許可されない
     */
    public function test_other_users_email_fails_validation_even_with_exclude(): void
    {
        $user1 = User::factory()->create([
            'email' => 'user1@example.com',
        ]);

        $user2 = User::factory()->create([
            'email' => 'user2@example.com',
        ]);

        $response = $this->postJson('/validate/email', [
            'email' => 'user2@example.com',
            'exclude_user_id' => $user1->id, // user1が編集中
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => false,
                'message' => 'このメールアドレスは既に使用されています。',
            ]);
    }

    /**
     * 空のメールアドレスでエラーになる
     */
    public function test_empty_email_fails_validation(): void
    {
        $response = $this->postJson('/validate/email', [
            'email' => '',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => false,
                'message' => 'メールアドレスを入力してください。',
            ]);
    }

    /**
     * 不正な形式のメールアドレスでエラーになる
     */
    public function test_invalid_email_format_fails_validation(): void
    {
        $response = $this->postJson('/validate/email', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => false,
                'message' => '有効なメールアドレスを入力してください。',
            ]);
    }
}
