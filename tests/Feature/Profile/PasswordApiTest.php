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
        'password' => Hash::make('oldpassword123'),
    ]);

    // Sanctum認証
    Sanctum::actingAs($user);

    // リクエスト
    $response = $this->putJson('/api/profile/password', [
        'current_password' => 'oldpassword123',
        'password' => 'newpassword456',
        'password_confirmation' => 'newpassword456',
    ]);

    // アサーション
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'パスワードを更新しました',
    ]);

    // DBのパスワードが更新されているか確認
    $user->refresh();
    expect(Hash::check('newpassword456', $user->password))->toBeTrue();
});

test('現在のパスワードが間違っている場合エラーを返す', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword123'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/password', [
        'current_password' => 'wrongpassword',
        'password' => 'newpassword456',
        'password_confirmation' => 'newpassword456',
    ]);

    // 現在のパスワード不一致はバリデーションエラー
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['current_password']);
});

test('新しいパスワードが8文字未満の場合エラーを返す', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword123'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/password', [
        'current_password' => 'oldpassword123',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    // バリデーションエラー
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
});

test('新しいパスワードと確認用が一致しない場合エラーを返す', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword123'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/password', [
        'current_password' => 'oldpassword123',
        'password' => 'newpassword456',
        'password_confirmation' => 'differentpassword',
    ]);

    // バリデーションエラー
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
});

test('現在のパスワードが未入力の場合エラーを返す', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword123'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/password', [
        'current_password' => '',
        'password' => 'newpassword456',
        'password_confirmation' => 'newpassword456',
    ]);

    // バリデーションエラー
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['current_password']);
});

test('新しいパスワードが未入力の場合エラーを返す', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword123'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/password', [
        'current_password' => 'oldpassword123',
        'password' => '',
        'password_confirmation' => '',
    ]);

    // バリデーションエラー
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
});

test('未認証ユーザーはアクセスできない', function () {
    $response = $this->putJson('/api/profile/password', [
        'current_password' => 'oldpassword123',
        'password' => 'newpassword456',
        'password_confirmation' => 'newpassword456',
    ]);

    // 未認証エラー
    $response->assertStatus(401);
});

test('複雑なパスワードに変更できる', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword123'),
    ]);

    Sanctum::actingAs($user);

    $newPassword = 'NewP@ssw0rd!2024';

    $response = $this->putJson('/api/profile/password', [
        'current_password' => 'oldpassword123',
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ]);

    $response->assertStatus(200);
    $user->refresh();
    expect(Hash::check($newPassword, $user->password))->toBeTrue();
});

test('パスワード確認フィールドが未入力の場合エラーを返す', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword123'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/password', [
        'current_password' => 'oldpassword123',
        'password' => 'newpassword456',
        // password_confirmation省略
    ]);

    // バリデーションエラー
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
});

