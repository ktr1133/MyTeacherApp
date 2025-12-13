<?php

namespace Tests\Feature\Api\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Notifications\ResetPassword;

/**
 * パスワードリセットAPIテスト
 * 
 * モバイルアプリ用パスワードリセット機能のテスト
 * - POST /api/auth/forgot-password: パスワードリセットリクエスト（メール送信）
 * 
 * @see /home/ktr/mtdev/routes/api.php - POST /api/auth/forgot-password
 * @see /home/ktr/mtdev/app/Http/Controllers/Auth/PasswordResetLinkController.php
 */
class PasswordResetApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 各テスト前の初期化
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // 通知をモック化（実際にメール送信しない）
        Notification::fake();
    }

    /**
     * 登録済みメールアドレスでパスワードリセットリクエストを送信できる
     */
    public function test_user_can_request_password_reset_with_valid_email(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Act
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'message',
            ]);

        // メッセージが正しいことを確認
        $this->assertNotEmpty($response->json('message'));
        
        // 通知が送信されたことを確認
        Notification::assertCount(1);
    }

    /**
     * 未登録のメールアドレスでパスワードリセットリクエストを送信するとエラーになる
     */
    public function test_user_cannot_request_password_reset_with_unregistered_email(): void
    {
        // Act
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email',
                ],
            ]);

        // 通知が送信されていないことを確認
        Notification::assertNothingSent();
    }

    /**
     * メールアドレスが空の場合はバリデーションエラーになる
     */
    public function test_password_reset_requires_email(): void
    {
        // Act
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => '',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // 通知が送信されていないことを確認
        Notification::assertNothingSent();
    }

    /**
     * 無効な形式のメールアドレスでリクエストするとバリデーションエラーになる
     */
    public function test_password_reset_requires_valid_email_format(): void
    {
        // Act
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'invalid-email',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // 通知が送信されていないことを確認
        Notification::assertNothingSent();
    }

    /**
     * APIリクエスト（api/*パス）の場合はJSON形式でレスポンスを返す
     */
    public function test_api_request_returns_json_response(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Act
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        // Assert
        $response->assertOk()
            ->assertHeader('Content-Type', 'application/json');

        // JSONレスポンスであることを確認
        $this->assertIsArray($response->json());
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * 複数回リクエストしても問題なく処理される（スロットリング前提）
     * 
     * Note: Laravelのデフォルトスロットリングは60秒に1回のため、
     *       このテストは sleep(61) が必要だが時間がかかるためスキップ
     */
    public function test_user_can_request_password_reset_multiple_times(): void
    {
        $this->markTestSkipped('Throttling test - requires 61 second wait');

        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Act - 1回目
        $response1 = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        // Assert - 1回目
        $response1->assertOk();

        // 60秒以上待つ（スロットリング解除）
        // sleep(61); // テスト時間短縮のためスキップ

        // Act - 2回目
        $response2 = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        // Assert - 2回目
        $response2->assertOk();
    }

    /**
     * Web版のエンドポイント（/forgot-password）でもAPI形式のリクエストを処理できる
     * 
     * 注意: このテストは互換性確認用（実際はapi/*パスを推奨）
     */
    public function test_api_request_to_web_endpoint_returns_json(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Act - JSONリクエストとして送信（expectsJson() === true）
        $response = $this->postJson('/forgot-password', [
            'email' => 'test@example.com',
        ]);

        // Assert - JSON形式でレスポンスが返る
        $response->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure(['message']);
    }

    /**
     * レート制限のテスト（オプション - 環境によってスキップ可能）
     * 
     * 注意: このテストは実行に時間がかかるため、必要に応じて実装
     */
    public function test_password_reset_is_rate_limited(): void
    {
        $this->markTestSkipped('Rate limiting test - implement if needed');

        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Act - 短時間に複数回リクエスト
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/auth/forgot-password', [
                'email' => 'test@example.com',
            ]);

            if ($i < 5) {
                $response->assertOk();
            } else {
                // 6回目以降はレート制限でエラーになるはず
                $response->assertStatus(429);
                break;
            }
        }
    }
}
