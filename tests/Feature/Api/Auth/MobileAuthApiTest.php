<?php

namespace Tests\Feature\Api\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

/**
 * モバイル認証APIテスト
 * 
 * Phase 2.B-2: モバイルアプリ認証機能のテスト
 * - POST /api/auth/login: ログイン（Sanctumトークン発行）
 * - POST /api/auth/logout: ログアウト（Sanctumトークン削除）
 */
class MobileAuthApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト用ユーザーデータ
     */
    private array $testUserData = [
        'username' => 'test_user',
        'email' => 'test@example.com',
        'name' => 'Test User',
        'password' => 'password123',
        'auth_provider' => 'mobile',
        'timezone' => 'Asia/Tokyo',
    ];

    /**
     * 正しいusername/passwordでログインできる
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        // Arrange
        $user = User::factory()->create([
            'username' => $this->testUserData['username'],
            'email' => $this->testUserData['email'],
            'password' => bcrypt($this->testUserData['password']),
            'auth_provider' => 'mobile',
        ]);

        // Act
        $response = $this->postJson('/api/auth/login', [
            'username' => $this->testUserData['username'],
            'password' => $this->testUserData['password'],
        ]);

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'username',
                    'avatar_url',
                    'created_at',
                ],
            ]);

        $this->assertNotEmpty($response->json('token'));
        $this->assertEquals($user->id, $response->json('user.id'));
        $this->assertEquals($user->username, $response->json('user.username'));

        // トークンが実際にDBに保存されていることを確認
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'mobile-app',
        ]);
    }

    /**
     * 間違ったusernameでログインできない
     */
    public function test_user_cannot_login_with_invalid_username(): void
    {
        // Arrange
        User::factory()->create([
            'username' => $this->testUserData['username'],
            'password' => bcrypt($this->testUserData['password']),
        ]);

        // Act
        $response = $this->postJson('/api/auth/login', [
            'username' => 'wrong_username',
            'password' => $this->testUserData['password'],
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    /**
     * 間違ったpasswordでログインできない
     */
    public function test_user_cannot_login_with_invalid_password(): void
    {
        // Arrange
        User::factory()->create([
            'username' => $this->testUserData['username'],
            'password' => bcrypt($this->testUserData['password']),
        ]);

        // Act
        $response = $this->postJson('/api/auth/login', [
            'username' => $this->testUserData['username'],
            'password' => 'wrong_password',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    /**
     * usernameが空の場合ログインできない
     */
    public function test_login_requires_username(): void
    {
        // Act
        $response = $this->postJson('/api/auth/login', [
            'password' => $this->testUserData['password'],
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    /**
     * passwordが空の場合ログインできない
     */
    public function test_login_requires_password(): void
    {
        // Act
        $response = $this->postJson('/api/auth/login', [
            'username' => $this->testUserData['username'],
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * ログイン成功時にlast_login_atが更新される
     */
    public function test_last_login_at_is_updated_on_successful_login(): void
    {
        // Arrange
        $user = User::factory()->create([
            'username' => $this->testUserData['username'],
            'password' => bcrypt($this->testUserData['password']),
            'last_login_at' => null,
        ]);

        $this->assertNull($user->last_login_at);

        // Act
        $this->postJson('/api/auth/login', [
            'username' => $this->testUserData['username'],
            'password' => $this->testUserData['password'],
        ]);

        // Assert
        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertEqualsWithDelta(
            now()->timestamp,
            $user->last_login_at->timestamp,
            5 // 5秒の誤差を許容
        );
    }

    /**
     * ログアウトできる（Sanctumトークン削除）
     */
    public function test_user_can_logout(): void
    {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('mobile-app')->plainTextToken;

        // トークンが存在することを確認
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        // Assert
        $response->assertOk()
            ->assertJson([
                'message' => 'ログアウトしました',
            ]);

        // トークンが削除されていることを確認
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
        ]);
    }

    /**
     * トークンなしでログアウトできない
     */
    public function test_cannot_logout_without_token(): void
    {
        // Act
        $response = $this->postJson('/api/auth/logout');

        // Assert
        $response->assertUnauthorized();
    }

    /**
     * 無効なトークンでログアウトできない
     */
    public function test_cannot_logout_with_invalid_token(): void
    {
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->postJson('/api/auth/logout');

        // Assert
        $response->assertUnauthorized();
    }

    /**
     * Sanctumトークンで認証済みAPIにアクセスできる
     */
    public function test_can_access_protected_api_with_sanctum_token(): void
    {
        // Arrange
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Act - タスクAPI（認証必須）にアクセス
        $response = $this->getJson('/api/tasks');

        // Assert - 401ではなく200または他の正常なステータス
        $response->assertOk();
    }

    /**
     * Sanctumトークンなしで認証済みAPIにアクセスできない
     */
    public function test_cannot_access_protected_api_without_token(): void
    {
        // Act
        $response = $this->getJson('/api/tasks');

        // Assert
        $response->assertUnauthorized();
    }

    /**
     * ログインレスポンスにuser情報が含まれる
     */
    public function test_login_response_includes_user_information(): void
    {
        // Arrange
        $user = User::factory()->create([
            'username' => $this->testUserData['username'],
            'email' => $this->testUserData['email'],
            'name' => $this->testUserData['name'],
            'password' => bcrypt($this->testUserData['password']),
        ]);

        // Act
        $response = $this->postJson('/api/auth/login', [
            'username' => $this->testUserData['username'],
            'password' => $this->testUserData['password'],
        ]);

        // Assert
        $response->assertOk();
        
        $userData = $response->json('user');
        $this->assertEquals($user->id, $userData['id']);
        $this->assertEquals($user->name, $userData['name']);
        $this->assertEquals($user->email, $userData['email']);
        $this->assertEquals($user->username, $userData['username']);
        $this->assertArrayHasKey('avatar_url', $userData);
        $this->assertArrayHasKey('created_at', $userData);
    }

    /**
     * Sanctumトークンの有効期限が30日に設定される
     */
    public function test_sanctum_token_has_30_days_expiration(): void
    {
        // Arrange
        $user = User::factory()->create([
            'username' => $this->testUserData['username'],
            'password' => bcrypt($this->testUserData['password']),
        ]);

        // Act
        $response = $this->postJson('/api/auth/login', [
            'username' => $this->testUserData['username'],
            'password' => $this->testUserData['password'],
        ]);

        // Assert
        $response->assertOk();

        // DBからトークン情報を取得
        $tokenRecord = \DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->id)
            ->first();

        $this->assertNotNull($tokenRecord);
        $this->assertNotNull($tokenRecord->expires_at);

        // 有効期限が約30日後であることを確認（誤差1日許容）
        $expectedExpiry = now()->addDays(30);
        $actualExpiry = \Carbon\Carbon::parse($tokenRecord->expires_at);
        
        $this->assertEqualsWithDelta(
            $expectedExpiry->timestamp,
            $actualExpiry->timestamp,
            86400 // 1日の秒数（誤差許容）
        );
    }

    /**
     * 削除済みユーザーはログインできない
     */
    public function test_soft_deleted_user_cannot_login(): void
    {
        // Arrange
        $user = User::factory()->create([
            'username' => $this->testUserData['username'],
            'password' => bcrypt($this->testUserData['password']),
        ]);
        $user->delete(); // ソフトデリート

        // Act
        $response = $this->postJson('/api/auth/login', [
            'username' => $this->testUserData['username'],
            'password' => $this->testUserData['password'],
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    /**
     * 複数回ログインすると複数のトークンが発行される
     */
    public function test_multiple_logins_create_multiple_tokens(): void
    {
        // Arrange
        $user = User::factory()->create([
            'username' => $this->testUserData['username'],
            'password' => bcrypt($this->testUserData['password']),
        ]);

        // Act - 2回ログイン
        $response1 = $this->postJson('/api/auth/login', [
            'username' => $this->testUserData['username'],
            'password' => $this->testUserData['password'],
        ]);

        $response2 = $this->postJson('/api/auth/login', [
            'username' => $this->testUserData['username'],
            'password' => $this->testUserData['password'],
        ]);

        // Assert
        $response1->assertOk();
        $response2->assertOk();

        $token1 = $response1->json('token');
        $token2 = $response2->json('token');

        $this->assertNotEquals($token1, $token2);

        // 2つのトークンがDBに存在することを確認
        $tokenCount = \DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->id)
            ->count();

        $this->assertEquals(2, $tokenCount);
    }
}
