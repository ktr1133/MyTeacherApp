<?php

use App\Models\User;
use App\Services\Auth\LoginAttemptService;
use Illuminate\Support\Facades\Hash;

describe('AdminLoginSecurity', function () {
    beforeEach(function () {
        $this->loginAttemptService = app(LoginAttemptService::class);
    });

    test('管理者ユーザーはログインできる', function () {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('Password123!'),
            'is_admin' => true,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($admin);
    });

    test('一般ユーザーは管理者エリアにアクセスできない', function () {
        $user = User::factory()->create([
            'email' => 'user@test.com',
            'password' => Hash::make('Password123!'),
            'is_admin' => false,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'user@test.com',
            'password' => 'Password123!',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    });

    test('5回のログイン失敗でアカウントがロックされる', function () {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('Password123!'),
            'is_admin' => true,
        ]);

        // 5回失敗
        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', [
                'email' => 'admin@test.com',
                'password' => 'WrongPassword999!',
            ]);
        }

        $admin->refresh();
        expect($admin->is_locked)->toBeTrue();
        expect($admin->failed_login_attempts)->toBe(5);
        expect($admin->locked_reason)->toContain('連続ログイン失敗');
    });

    test('ロックされたアカウントはログインできない', function () {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('Password123!'),
            'is_admin' => true,
            'is_locked' => true,
            'locked_at' => now(),
            'locked_reason' => 'テストロック',
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'Password123!',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    });

    test('ログイン成功で失敗カウントがリセットされる', function () {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('Password123!'),
            'is_admin' => true,
            'failed_login_attempts' => 3,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $admin->refresh();
        expect($admin->failed_login_attempts)->toBe(0);
    });

    test('ログイン試行履歴が記録される', function () {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'wrong-password',
        ]);

        $this->assertDatabaseHas('login_attempts', [
            'email' => 'admin@test.com',
            'successful' => false,
        ]);
    });

    test('疑わしいIPがブロックされる', function () {
        config(['admin.ip_restriction_enabled' => false]); // IP制限は無効

        // 短時間に大量の試行
        for ($i = 0; $i < 11; $i++) {
            $this->loginAttemptService->recordAttempt(
                'test@test.com',
                '192.168.1.100',
                false,
                'テスト失敗'
            );
        }

        expect($this->loginAttemptService->isSuspiciousIp('192.168.1.100'))
            ->toBeTrue();
    });
});

describe('AdminIpRestriction', function () {
    test('許可されていないIPはブロックされる', function () {
        config(['admin.ip_restriction_enabled' => true]);
        config(['admin.allowed_ips' => ['192.168.1.1']]);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $response = $this->get('/admin/dashboard');
        $response->assertStatus(403);
    });

    test('許可されたIPはアクセスできる', function () {
        config(['admin.ip_restriction_enabled' => true]);
        config(['admin.allowed_ips' => ['127.0.0.1']]);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $response = $this->get('/admin/dashboard');
        $response->assertOk();
    });
});

describe('LoginAttemptService', function () {
    beforeEach(function () {
        $this->service = app(LoginAttemptService::class);
    });

    test('アカウントロック解除が正常に動作する', function () {
        $user = User::factory()->create([
            'is_locked' => true,
            'locked_at' => now(),
            'locked_reason' => 'テスト',
            'failed_login_attempts' => 5,
        ]);

        $this->service->unlockAccount($user);

        $user->refresh();
        expect($user->is_locked)->toBeFalse();
        expect($user->failed_login_attempts)->toBe(0);
    });

    test('ログイン試行履歴を取得できる', function () {
        $user = User::factory()->create(['email' => 'test@test.com']);

        // 試行記録
        $this->service->recordAttempt('test@test.com', '127.0.0.1', false);
        $this->service->recordAttempt('test@test.com', '127.0.0.1', true);

        $history = $this->service->getAttemptHistory('test@test.com', 10);

        expect($history)->toHaveCount(2);
    });
});
