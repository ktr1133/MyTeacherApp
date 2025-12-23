<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

/**
 * パスワード更新API テスト
 * 
 * エンドポイント: PUT /api/profile/password
 * Action: UpdatePasswordApiAction
 * Service: ProfileManagementService::updatePassword()
 */

test('パスワード更新が成功する', function () {
    // ユーザー作成
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
    ]);

    // Sanctum認証
    Sanctum::actingAs($user);

    // リクエスト
    $response = $this->putJson('/api/profile/password', [
        'current_password' => 'OldPassword123!',
        'password' => 'NewPassword456!',
        'password_confirmation' => 'NewPassword456!',
    ]);

    // アサーション
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'パスワードを更新しました',
    ]);

    // DBのパスワードが更新されているか確認
    $user->refresh();
    expect(Hash::check('NewPassword456!', $user->password))->toBeTrue();
});

test('現在のパスワードが間違っている場合エラーを返す', function () {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/password', [
        'current_password' => 'WrongPassword999!',
        'password' => 'NewPassword456!',
        'password_confirmation' => 'NewPassword456!',
    ]);

    // 現在のパスワード不一致はバリデーションエラー
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['current_password']);
});

test('新しいパスワードが8文字未満の場合エラーを返す', function () {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/password', [
        'current_password' => 'OldPassword123!',
        'password' => 'Short1!',
        'password_confirmation' => 'Short1!',
    ]);

    // バリデーションエラー
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
});

test('新しいパスワードと確認用が一致しない場合エラーを返す', function () {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/password', [
        'current_password' => 'OldPassword123!',
        'password' => 'NewPassword456!',
        'password_confirmation' => 'DifferentPass789!',
    ]);

    // バリデーションエラー
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
});

test('現在のパスワードが未入力の場合エラーを返す', function () {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/password', [
        'current_password' => '',
        'password' => 'NewPassword456!',
        'password_confirmation' => 'NewPassword456!',
    ]);

    // バリデーションエラー
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['current_password']);
});

test('新しいパスワードが未入力の場合エラーを返す', function () {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/password', [
        'current_password' => 'OldPassword123!',
        'password' => '',
        'password_confirmation' => '',
    ]);

    // バリデーションエラー
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
});

test('未認証ユーザーはアクセスできない', function () {
    $response = $this->putJson('/api/profile/password', [
        'current_password' => 'OldPassword123!',
        'password' => 'NewPassword456!',
        'password_confirmation' => 'NewPassword456!',
    ]);

    // 未認証エラー
    $response->assertStatus(401);
});

test('複雑なパスワードに変更できる', function () {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
    ]);

    Sanctum::actingAs($user);

    $newPassword = 'NewP@ssw0rd!2024';

    $response = $this->putJson('/api/profile/password', [
        'current_password' => 'OldPassword123!',
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ]);

    $response->assertStatus(200);
    $user->refresh();
    expect(Hash::check($newPassword, $user->password))->toBeTrue();
});

test('パスワード確認フィールドが未入力の場合エラーを返す', function () {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/password', [
        'current_password' => 'OldPassword123!',
        'password' => 'NewPassword456!',
        // password_confirmation省略
    ]);

    // バリデーションエラー
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
});

test('英字が含まれていない場合エラーを返す', function () {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/password', [
        'current_password' => 'OldPassword123!',
        'password' => '12345678!@',
        'password_confirmation' => '12345678!@',
    ]);

    // バリデーションエラー（英字が含まれていない）
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
});

test('大文字が含まれていない場合エラーを返す', function () {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/password', [
        'current_password' => 'OldPassword123!',
        'password' => 'newpassword123!',
        'password_confirmation' => 'newpassword123!',
    ]);

    // バリデーションエラー（大文字が含まれていない）
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
});

test('小文字が含まれていない場合エラーを返す', function () {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/password', [
        'current_password' => 'OldPassword123!',
        'password' => 'NEWPASSWORD123!',
        'password_confirmation' => 'NEWPASSWORD123!',
    ]);

    // バリデーションエラー（小文字が含まれていない）
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
});

test('数字が含まれていない場合エラーを返す', function () {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/password', [
        'current_password' => 'OldPassword123!',
        'password' => 'NewPassword!',
        'password_confirmation' => 'NewPassword!',
    ]);

    // バリデーションエラー（数字が含まれていない）
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
});

test('記号が含まれていない場合エラーを返す', function () {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/password', [
        'current_password' => 'OldPassword123!',
        'password' => 'NewPassword123',
        'password_confirmation' => 'NewPassword123',
    ]);

    // バリデーションエラー（記号が含まれていない）
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
});

test('全ての要件を満たすパスワードは受け入れられる', function () {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
    ]);

    Sanctum::actingAs($user);

    // 英字（大小）、数字、記号を含む8文字以上
    // ランダムな文字列でデータ漏洩に含まれないパスワード
    $validPasswords = [
        'ValidPass1!Xz',
        'Str0ng@Pa$$Qw',
        'Secure#Pass9Km',
        'Test#1234Aa!Zx',
    ];

    $currentPassword = 'OldPassword123!';

    foreach ($validPasswords as $validPassword) {
        $response = $this->putJson('/api/profile/password', [
            'current_password' => $currentPassword,
            'password' => $validPassword,
            'password_confirmation' => $validPassword,
        ]);

        $response->assertStatus(200);
        
        // 次のテストのためにパスワードを更新
        $user->update(['password' => Hash::make($validPassword)]);
        $user->refresh();
        $currentPassword = $validPassword; // 次のループの現在パスワードを更新
    }
});

