<?php

use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
})->skip('ルート設定がテスト環境で利用できないためスキップ');

test('new users can register with consent', function () {
    $response = $this->post('/register', [
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'RegTest#8Kp2',
        'password_confirmation' => 'RegTest#8Kp2',
        'timezone' => 'Asia/Tokyo',
        'privacy_policy_consent' => '1',
        'terms_consent' => '1',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('avatars.create', absolute: false));
    
    // 同意データが記録されていることを確認
    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->privacy_policy_version)->toBe(config('legal.current_versions.privacy_policy'));
    expect($user->terms_version)->toBe(config('legal.current_versions.terms_of_service'));
    expect($user->privacy_policy_agreed_at)->not->toBeNull();
    expect($user->terms_agreed_at)->not->toBeNull();
});

test('registration requires privacy policy consent', function () {
    $response = $this->post('/register', [
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'RegTest#8Kp2',
        'password_confirmation' => 'RegTest#8Kp2',
        'timezone' => 'Asia/Tokyo',
        // privacy_policy_consent なし
        'terms_consent' => '1',
    ]);

    $response->assertSessionHasErrors(['privacy_policy_consent']);
    $this->assertGuest();
});

test('registration requires terms consent', function () {
    $response = $this->post('/register', [
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'RegTest#8Kp2',
        'password_confirmation' => 'RegTest#8Kp2',
        'timezone' => 'Asia/Tokyo',
        'privacy_policy_consent' => '1',
        // terms_consent なし
    ]);

    $response->assertSessionHasErrors(['terms_consent']);
    $this->assertGuest();
});

test('registration requires both consents', function () {
    $response = $this->post('/register', [
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'RegTest#8Kp2',
        'password_confirmation' => 'RegTest#8Kp2',
        'timezone' => 'Asia/Tokyo',
        // 両方の同意なし
    ]);

    $response->assertSessionHasErrors(['privacy_policy_consent', 'terms_consent']);
    $this->assertGuest();
});

test('registration records correct consent versions', function () {
    // 設定ファイルのバージョンを確認
    $privacyVersion = config('legal.current_versions.privacy_policy');
    $termsVersion = config('legal.current_versions.terms_of_service');
    
    expect($privacyVersion)->not->toBeNull();
    expect($termsVersion)->not->toBeNull();
    
    $this->post('/register', [
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'RegTest#8Kp2',
        'password_confirmation' => 'RegTest#8Kp2',
        'timezone' => 'Asia/Tokyo',
        'privacy_policy_consent' => '1',
        'terms_consent' => '1',
    ]);

    $user = User::where('email', 'test@example.com')->first();
    
    // バージョンが正しく記録されていることを確認
    expect($user->privacy_policy_version)->toBe($privacyVersion);
    expect($user->terms_version)->toBe($termsVersion);
    
    // タイムスタンプが現在時刻に近いことを確認（5秒の誤差許容）
    expect($user->privacy_policy_agreed_at->timestamp)
        ->toBeGreaterThanOrEqual(now()->subSeconds(5)->timestamp);
    expect($user->terms_agreed_at->timestamp)
        ->toBeGreaterThanOrEqual(now()->subSeconds(5)->timestamp);
});
