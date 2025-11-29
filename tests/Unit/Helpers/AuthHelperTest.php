<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Helpers\AuthHelper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

class AuthHelperTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * Cognito情報から新規ユーザーを作成できること
     */
    public function test_can_create_new_user_from_cognito_info(): void
    {
        // Arrange
        $cognitoSub = 'cognito-sub-12345';
        $email = 'test@example.com';
        $username = 'testuser';

        // Act
        $user = AuthHelper::getOrCreateCognitoUser($cognitoSub, $email, $username);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('cognito-sub-12345', $user->cognito_sub);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('testuser', $user->username);
        $this->assertEquals('cognito', $user->auth_provider);
        $this->assertDatabaseHas('users', [
            'cognito_sub' => 'cognito-sub-12345',
            'email' => 'test@example.com',
        ]);
    }

    /**
     * @test
     * 既存のCognitoユーザーを取得できること
     */
    public function test_can_retrieve_existing_cognito_user(): void
    {
        // Arrange
        $existingUser = User::factory()->create([
            'cognito_sub' => 'cognito-sub-existing',
            'email' => 'existing@example.com',
            'username' => 'existinguser',
            'auth_provider' => 'cognito',
        ]);

        // Act
        $user = AuthHelper::getOrCreateCognitoUser('cognito-sub-existing', 'existing@example.com', 'existinguser');

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($existingUser->id, $user->id);
        $this->assertEquals('cognito-sub-existing', $user->cognito_sub);
        $this->assertEquals(1, User::where('cognito_sub', 'cognito-sub-existing')->count());
    }

    /**
     * @test
     * ユーザー名重複時に連番を付与できること
     */
    public function test_can_add_sequential_number_when_username_duplicates(): void
    {
        // Arrange
        User::factory()->create(['username' => 'testuser']);
        User::factory()->create(['username' => 'testuser1']);

        // Act
        $user = AuthHelper::getOrCreateCognitoUser('cognito-sub-new', 'new@example.com', 'testuser');

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('testuser2', $user->username); // 連番2が付与される
    }

    /**
     * @test
     * getCognitoInfo がリクエストから情報を取得できること
     */
    public function test_get_cognito_info_can_retrieve_information_from_request(): void
    {
        // Arrange
        $request = Request::create('/test', 'GET');
        $request->attributes->set('cognito_sub', 'sub-123');
        $request->attributes->set('email', 'info@example.com');
        $request->attributes->set('username', 'infouser');

        // Act
        $info = AuthHelper::getCognitoInfo($request);

        // Assert
        $this->assertIsArray($info);
        $this->assertEquals('sub-123', $info['cognito_sub']);
        $this->assertEquals('info@example.com', $info['email']);
        $this->assertEquals('infouser', $info['username']);
    }

    /**
     * @test
     * getAuthProvider がCognitoユーザーを判定できること
     */
    public function test_get_auth_provider_can_identify_cognito_user(): void
    {
        // Arrange
        $cognitoUser = User::factory()->create([
            'auth_provider' => 'cognito',
        ]);

        // Act
        $provider = AuthHelper::getAuthProvider($cognitoUser);

        // Assert
        $this->assertEquals('cognito', $provider);
    }

    /**
     * @test
     * getAuthProvider がBreezeユーザーを判定できること
     */
    public function test_get_auth_provider_can_identify_breeze_user(): void
    {
        // Arrange
        $breezeUser = User::factory()->create([
            'auth_provider' => 'breeze',
        ]);

        // Act
        $provider = AuthHelper::getAuthProvider($breezeUser);

        // Assert
        $this->assertEquals('breeze', $provider);
    }

    /**
     * @test
     * getAuthProvider がnullユーザーでunknownを返すこと
     */
    public function test_get_auth_provider_returns_unknown_for_null_user(): void
    {
        // Act
        $provider = AuthHelper::getAuthProvider(null);

        // Assert
        $this->assertEquals('unknown', $provider);
    }

    /**
     * @test
     * generateUsernameFromEmail がメールアドレスからユーザー名を生成できること
     */
    public function test_generate_username_from_email_can_create_username_from_email_address(): void
    {
        // Arrange
        $email = 'testuser@example.com';

        // Act - generateUsernameFromEmailはprivateなので、getOrCreateCognitoUser経由でテスト
        $user = AuthHelper::getOrCreateCognitoUser('cognito-sub-username-test', $email);

        // Assert
        $this->assertEquals('testuser', $user->username);
    }

    /**
     * @test
     * generateUsernameFromEmail で重複がある場合連番を付与すること
     */
    public function test_generate_username_from_email_adds_sequential_number_when_duplicate_exists(): void
    {
        // Arrange
        User::factory()->create(['username' => 'duplicate']);
        User::factory()->create(['username' => 'duplicate1']);
        User::factory()->create(['username' => 'duplicate2']);

        $email = 'duplicate@example.com';

        // Act - generateUsernameFromEmailはprivateなので、getOrCreateCognitoUser経由でテスト
        $user = AuthHelper::getOrCreateCognitoUser('cognito-sub-duplicate-test', $email);

        // Assert
        $this->assertEquals('duplicate3', $user->username);
    }

    /**
     * @test
     * メールアドレスの@より前の部分をユーザー名として使用すること
     */
    public function test_uses_part_before_at_symbol_in_email_address_as_username(): void
    {
        // Arrange
        $email = 'john.doe+test@example.co.jp';

        // Act - generateUsernameFromEmailはprivateなので、getOrCreateCognitoUser経由でテスト
        $user = AuthHelper::getOrCreateCognitoUser('cognito-sub-email-format-test', $email);

        // Assert
        $this->assertEquals('john.doe+test', $user->username);
    }
}
