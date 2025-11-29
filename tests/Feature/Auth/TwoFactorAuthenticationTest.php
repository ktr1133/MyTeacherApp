<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 2FA有効化テスト
     */
    public function test_two_factor_authentication_can_be_enabled(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
            ->post('/user/two-factor-authentication');

        $user->refresh();

        $this->assertNotNull($user->two_factor_secret);
        $this->assertNotNull($user->two_factor_recovery_codes);
        $response->assertSessionHasNoErrors();
    }

    /**
     * 2FA無効化テスト
     */
    public function test_two_factor_authentication_can_be_disabled(): void
    {
        $user = User::factory()->create();

        // 2FA有効化
        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
            ->post('/user/two-factor-authentication');
        
        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);

        // 2FA無効化
        $response = $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
            ->delete('/user/two-factor-authentication');

        $user->refresh();

        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
        $response->assertSessionHasNoErrors();
    }

    /**
     * QRコード取得テスト
     */
    public function test_qr_code_can_be_retrieved(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
            ->post('/user/two-factor-authentication');

        $user->refresh();

        $qrCode = $user->twoFactorQrCodeSvg();

        $this->assertStringContainsString('svg', $qrCode);
    }

    /**
     * リカバリーコード再生成テスト
     */
    public function test_recovery_codes_can_be_regenerated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
            ->post('/user/two-factor-authentication');

        $user->refresh();
        $originalCodes = $user->two_factor_recovery_codes;

        $response = $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
            ->post('/user/two-factor-recovery-codes');

        $user->refresh();
        $newCodes = $user->two_factor_recovery_codes;

        $this->assertNotEquals($originalCodes, $newCodes);
        $response->assertSessionHasNoErrors();
    }

    /**
     * 2FA設定画面が表示されることを確認
     */
    public function test_two_factor_settings_displayed_in_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/edit');

        $response->assertOk();
        $response->assertSee('二要素認証');
        $response->assertSee('認証アプリを使用してアカウントのセキュリティを強化します。');
    }

    /**
     * 2FA有効化後にQRコードが表示されることを確認
     */
    public function test_qr_code_displayed_after_enabling_2fa(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
            ->post('/user/two-factor-authentication');

        $response = $this->actingAs($user)->get('/profile/edit');

        $response->assertOk();
        $response->assertSee('認証アプリでQRコードをスキャン');
        $response->assertSee('svg'); // QRコードSVG
    }
}
