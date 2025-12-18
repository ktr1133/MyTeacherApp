<?php

use App\Models\User;

/**
 * 法的情報ページ（プライバシーポリシー・利用規約）のテスト
 * 
 * テスト対象:
 * - プライバシーポリシーページの表示
 * - 利用規約ページの表示
 * - プロフィール画面の法的情報セクション
 */

describe('プライバシーポリシーページ', function () {
    test('認証なしでプライバシーポリシーページが表示される', function () {
        $response = $this->get('/privacy-policy');

        $response->assertOk();
        $response->assertSee('プライバシーポリシー');
        $response->assertSee('個人情報の取り扱い');
    });

    test('プライバシーポリシーページのタイトルが正しい', function () {
        $response = $this->get('/privacy-policy');

        $response->assertOk();
        $response->assertSee('<title>プライバシーポリシー - My Teacher</title>', false);
    });

    test('プライバシーポリシーページに目次が表示される', function () {
        $response = $this->get('/privacy-policy');

        $response->assertOk();
        $response->assertSee('目次');
        $response->assertSee('はじめに');
        $response->assertSee('事業者情報');
        $response->assertSee('収集する個人情報');
    });

    test('プライバシーポリシーページにお問い合わせ先が表示される', function () {
        $response = $this->get('/privacy-policy');

        $response->assertOk();
        $response->assertSee('famicoapp@gmail.com');
    });

    test('プライバシーポリシーページにダークモード対応クラスが含まれる', function () {
        $response = $this->get('/privacy-policy');

        $response->assertOk();
        $response->assertSee('dark:bg-gray-900', false);
        $response->assertSee('dark:text-white', false);
    });
});

describe('利用規約ページ', function () {
    test('認証なしで利用規約ページが表示される', function () {
        $response = $this->get('/terms-of-service');

        $response->assertOk();
        $response->assertSee('利用規約');
        $response->assertSee('第1条');
    });

    test('利用規約ページのタイトルが正しい', function () {
        $response = $this->get('/terms-of-service');

        $response->assertOk();
        $response->assertSee('<title>利用規約 - My Teacher</title>', false);
    });

    test('利用規約ページに目次が表示される', function () {
        $response = $this->get('/terms-of-service');

        $response->assertOk();
        $response->assertSee('目次');
        $response->assertSee('定義');
        $response->assertSee('本規約への同意');
    });

    test('利用規約ページにお問い合わせ先が表示される', function () {
        $response = $this->get('/terms-of-service');

        $response->assertOk();
        $response->assertSee('famicoapp@gmail.com');
    });

    test('利用規約ページからプライバシーポリシーへのリンクがある', function () {
        $response = $this->get('/terms-of-service');

        $response->assertOk();
        $response->assertSee('privacy-policy', false);
    });
});

describe('プロフィール画面の法的情報セクション', function () {
    test('プロフィール画面に法的情報セクションが表示される', function () {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile/edit');

        $response->assertOk();
        $response->assertSee('法的情報');
    });

    test('プロフィール画面にプライバシーポリシーリンクが表示される', function () {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile/edit');

        $response->assertOk();
        $response->assertSee('プライバシーポリシー');
        $response->assertSee('privacy-policy', false);
    });

    test('プロフィール画面に利用規約リンクが表示される', function () {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile/edit');

        $response->assertOk();
        $response->assertSee('利用規約');
        $response->assertSee('terms-of-service', false);
    });

    test('子ども向けテーマでは法的情報セクションが「おやくそく」と表示される', function () {
        $user = User::factory()->create(['theme' => 'child']);

        $response = $this
            ->actingAs($user)
            ->get('/profile/edit');

        $response->assertOk();
        $response->assertSee('おやくそく');
        $response->assertSee('プライバシーについて');
    });

    test('法的情報リンクは新規タブで開く設定になっている', function () {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile/edit');

        $response->assertOk();
        $response->assertSee('target="_blank"', false);
    });
});

describe('フッターの法的情報リンク', function () {
    test('トップページのフッターにプライバシーポリシーリンクがある', function () {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('privacy-policy', false);
        $response->assertSee('プライバシーポリシー');
    });

    test('トップページのフッターに利用規約リンクがある', function () {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('terms-of-service', false);
        $response->assertSee('利用規約');
    });
});

describe('ルーティング', function () {
    test('プライバシーポリシーのルート名が正しい', function () {
        $route = route('privacy-policy');

        expect($route)->toBe(url('/privacy-policy'));
    });

    test('利用規約のルート名が正しい', function () {
        $route = route('terms-of-service');

        expect($route)->toBe(url('/terms-of-service'));
    });
});
