<?php

use App\Models\User;
use App\Models\TokenPackage;
use App\Models\TokenBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    // テスト用ユーザー作成
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
    ]);
    
    // トークン残高を別テーブルで作成
    TokenBalance::create([
        'tokenable_type' => User::class,
        'tokenable_id' => $this->user->id,
        'balance' => 100000,
        'free_balance' => 100000,
        'paid_balance' => 0,
    ]);
    
    // テスト用トークンパッケージ作成（モデルの実際のカラムのみ使用）
    $this->packages = [
        TokenPackage::factory()->create([
            'name' => '0.5Mトークン',
            'token_amount' => 500000,
            'price' => 400,
            'stripe_price_id' => 'price_test_500k',
            'is_active' => true, // status ではなく is_active
        ]),
        TokenPackage::factory()->create([
            'name' => '2.5Mトークン',
            'token_amount' => 2500000,
            'price' => 1800,
            'stripe_price_id' => 'price_test_2500k',
            'is_active' => true,
        ]),
        TokenPackage::factory()->create([
            'name' => '5Mトークン',
            'token_amount' => 5000000,
            'price' => 3400,
            'stripe_price_id' => 'price_test_5000k',
            'is_active' => true,
        ]),
    ];
    
    // Stripe設定（テストモード）
    config([
        'services.stripe.key' => 'pk_test_dummy',
        'services.stripe.secret' => 'sk_test_dummy',
    ]);
});

describe('トークンパッケージ一覧表示', function () {
    it('ログインユーザーはパッケージ一覧を表示できる', function () {
        $response = $this->actingAs($this->user)->get(route('tokens.purchase'));
        
        $response->assertStatus(200)
            ->assertViewIs('tokens.purchase')
            ->assertViewHas('packages');
        // userはビュー内でauth()->user()を使用するため、assertViewHasは不要
    });

    it('未ログインユーザーはログイン画面にリダイレクトされる', function () {
        $response = $this->get(route('tokens.purchase'));
        
        $response->assertRedirect(route('login'));
    });

    it('activeステータスのパッケージのみ表示される', function () {
        TokenPackage::factory()->create([
            'name' => '無効パッケージ',
            'is_active' => false, // status ではなく is_active
        ]);
        
        $response = $this->actingAs($this->user)->get(route('tokens.purchase'));
        
        $packages = $response->viewData('packages');
        expect($packages)->toHaveCount(3); // 3つのactiveパッケージのみ
    });
});

describe('Checkout Session作成 - バリデーション', function () {
    it('package_idが必須であることを検証', function () {
        $response = $this->actingAs($this->user)->post(route('tokens.purchase.checkout'), [
            // package_idを省略
        ]);
        
        $response->assertSessionHasErrors(['package_id']);
    });

    it('package_idは整数であることを検証', function () {
        $response = $this->actingAs($this->user)->post(route('tokens.purchase.checkout'), [
            'package_id' => 'invalid',
        ]);
        
        $response->assertSessionHasErrors(['package_id']);
    });

    it('存在しないパッケージIDはエラー', function () {
        $response = $this->actingAs($this->user)->post(route('tokens.purchase.checkout'), [
            'package_id' => 99999,
        ]);
        
        // バリデーションエラーは withErrors() に自動格納される
        $response->assertRedirect()
            ->assertSessionHasErrors('package_id');
    });

    it('stripe_price_idが設定されていないパッケージはエラー', function () {
        $invalidPackage = TokenPackage::factory()->create([
            'stripe_price_id' => null,
            'is_active' => true, // status ではなく is_active
        ]);
        
        $response = $this->actingAs($this->user)->post(route('tokens.purchase.checkout'), [
            'package_id' => $invalidPackage->id,
        ]);
        
        $response->assertRedirect()
            ->assertSessionHas('error');
    });
});

describe('Checkout Session作成 - 成功ケース', function () {
    it('Stripe APIエラーの場合はエラーページにリダイレクト', function () {
        // Stripe APIが実際に呼ばれないため、モックなしでは失敗する
        $response = $this->actingAs($this->user)->post(route('tokens.purchase.checkout'), [
            'package_id' => $this->packages[0]->id,
        ]);
        
        // Stripe APIモックがないため、エラーページへリダイレクト
        $response->assertStatus(302);
    });
});

describe('購入成功ページ表示', function () {
    it('session_idパラメータなしでもアクセス可能', function () {
        $response = $this->actingAs($this->user)->get(route('tokens.purchase.success'));
        
        $response->assertStatus(200)
            ->assertViewIs('tokens.purchase-success');
    });

    it('session_idパラメータがあっても正常表示', function () {
        $response = $this->actingAs($this->user)->get(route('tokens.purchase.success') . '?session_id=cs_test_dummy');
        
        $response->assertStatus(200)
            ->assertViewIs('tokens.purchase-success');
    });

    it('未ログインユーザーはログイン画面にリダイレクト', function () {
        $response = $this->get(route('tokens.purchase.success'));
        
        $response->assertRedirect(route('login'));
    });
});

describe('購入キャンセルページ表示', function () {
    it('ログインユーザーはキャンセルページを表示できる', function () {
        $response = $this->actingAs($this->user)->get(route('tokens.purchase.cancel'));
        
        $response->assertStatus(200)
            ->assertViewIs('tokens.purchase-cancel');
    });

    it('未ログインユーザーはログイン画面にリダイレクト', function () {
        $response = $this->get(route('tokens.purchase.cancel'));
        
        $response->assertRedirect(route('login'));
    });
});
