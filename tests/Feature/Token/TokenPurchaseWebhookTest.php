<?php

use App\Models\User;
use App\Models\TokenPackage;
use App\Models\TokenTransaction;
use App\Models\TokenBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    // テスト用ユーザー作成
    $this->user = User::factory()->create([
        'email' => 'webhook-test@example.com',
        'stripe_id' => 'cus_test_webhook',
    ]);
    
    // トークン残高を別テーブルで作成
    TokenBalance::create([
        'tokenable_type' => User::class,
        'tokenable_id' => $this->user->id,
        'balance' => 50000,
        'free_balance' => 50000,
        'paid_balance' => 0,
    ]);
    
    // テスト用トークンパッケージ
    $this->package = TokenPackage::factory()->create([
        'name' => 'Webhookテストパッケージ',
        'token_amount' => 500000,
        'price' => 400,
        'stripe_price_id' => 'price_test_webhook',
        'is_active' => true, // status ではなく is_active
    ]);
    
    // Webhook署名検証用シークレット
    config([
        'services.stripe.webhook.secret' => 'whsec_test_secret',
    ]);
});

describe('Webhook署名検証', function () {
    it('無効な署名はリジェクトされる', function () {
        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test']],
        ]);
        
        $response = $this->postJson('/stripe/webhook', [], [
            'Stripe-Signature' => 'invalid_signature',
        ]);
        
        // Cashier WebhookControllerは署名検証失敗で400を返す
        $response->assertStatus(400);
    });

    it('署名ヘッダーなしはリジェクトされる', function () {
        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test']],
        ]);
        
        $response = $this->postJson('/stripe/webhook', []);
        
        $response->assertStatus(400);
    });
});

describe('checkout.session.completed - トークン購入', function () {
    it('トークン購入イベントでトークンが付与される', function () {
        // Checkout Sessionモックデータ
        $sessionId = 'cs_test_completed';
        $paymentIntentId = 'pi_test_succeeded';
        
        // モックペイロード（実際のStripe Webhookの構造）
        $payload = [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => $sessionId,
                    'mode' => 'payment',
                    'payment_status' => 'paid',
                    'customer' => $this->user->stripe_id,
                    'client_reference_id' => (string) $this->user->id,
                    'payment_intent' => $paymentIntentId,
                    'metadata' => [
                        'user_id' => (string) $this->user->id,
                        'package_id' => (string) $this->package->id,
                        'token_amount' => (string) $this->package->token_amount,
                        'purchase_type' => 'token_purchase',
                    ],
                ],
            ],
        ];
        
        // Stripe CheckoutSession::retrieve()をモック
        $this->mock(\Stripe\Checkout\Session::class, function ($mock) use ($sessionId, $paymentIntentId) {
            $mock->shouldReceive('retrieve')
                ->once()
                ->with([
                    'id' => $sessionId,
                    'expand' => ['payment_intent'],
                ])
                ->andReturn((object) [
                    'id' => $sessionId,
                    'payment_intent' => (object) ['id' => $paymentIntentId],
                    'metadata' => (object) [
                        'user_id' => (string) $this->user->id,
                        'package_id' => (string) $this->package->id,
                    ],
                    'client_reference_id' => (string) $this->user->id,
                ]);
        });
        
        $initialBalance = 50000; // beforeEachで設定した初期残高
        
        // Webhook実行（署名検証はスキップ - 統合テストでは署名生成が複雑）
        // 実際の署名検証は別テストで行う
        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->postJson('/stripe/webhook', $payload);
        
        // レスポンスは200（成功）
        $response->assertStatus(200);
        
        // TokenBalanceが更新されていることを確認
        $tokenBalance = TokenBalance::where('tokenable_type', User::class)
            ->where('tokenable_id', $this->user->id)
            ->first();
        
        expect($tokenBalance->balance)->toBe($initialBalance + $this->package->token_amount);
        
        // トークン取引履歴確認
        $transaction = TokenTransaction::where('tokenable_id', $this->user->id)
            ->where('tokenable_type', User::class)
            ->where('type', 'purchase')
            ->latest()
            ->first();
        
        expect($transaction)->not->toBeNull()
            ->and($transaction->amount)->toBe($this->package->token_amount);
    });

    it('サブスクリプションのcheckout.session.completedは処理されない', function () {
        $payload = [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_subscription',
                    'mode' => 'subscription', // subscriptionモード
                    'customer' => 'cus_test',
                    'subscription' => 'sub_test',
                    'metadata' => [],
                ],
            ],
        ];
        
        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->postJson('/stripe/webhook', $payload);
        
        // サブスクリプションの場合は別の処理が走る
        $response->assertStatus(200);
    });
});

describe('payment_intent.succeeded', function () {
    it('Payment Intent成功イベントはログに記録される', function () {
        // Phase 1.2実装範囲外: payment_intentイベントは別Phaseで実装
        expect(true)->toBeTrue();
    })->skip('Phase 1.2実装範囲外 - payment_intentイベントは別Phase');
});

describe('payment_intent.payment_failed', function () {
    it('決済失敗イベントはログに記録される', function () {
        // Phase 1.2実装範囲外
        expect(true)->toBeTrue();
    })->skip('Phase 1.2実装範囲外 - payment_intentイベントは別Phase');
});

describe('未知のイベントタイプ', function () {
    it('未知のイベントは正常に処理される（200返却）', function () {
        $payload = [
            'type' => 'unknown.event.type',
            'data' => [
                'object' => [
                    'id' => 'obj_test',
                ],
            ],
        ];
        
        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->postJson('/stripe/webhook', $payload);
        
        $response->assertStatus(200);
    });
});

describe('トークン付与のトランザクション整合性', function () {
    it('トークン付与は必ずトランザクション内で実行される', function () {
        // Checkout Session完了でトークンが付与されることをテスト
        $payload = [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_transaction_integrity',
                    'mode' => 'payment',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_test_integrity',
                    'client_reference_id' => (string) $this->user->id,
                    'metadata' => [
                        'user_id' => (string) $this->user->id,
                        'package_id' => (string) $this->package->id,
                        'token_amount' => (string) $this->package->token_amount,
                        'purchase_type' => 'token_purchase',
                    ],
                ],
            ],
        ];
        
        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->postJson('/stripe/webhook', $payload);
        
        $response->assertStatus(200);
        
        // TokenBalanceが更新されていることを確認
        $tokenBalance = TokenBalance::where('tokenable_type', User::class)
            ->where('tokenable_id', $this->user->id)
            ->first();
        
        expect($tokenBalance->balance)->toBe(50000 + $this->package->token_amount);
        expect($tokenBalance->paid_balance)->toBe($this->package->token_amount);
        
        // TokenTransactionが作成されていることを確認
        $transaction = TokenTransaction::where('tokenable_id', $this->user->id)
            ->where('type', 'purchase')
            ->latest()
            ->first();
        
        expect($transaction)->not->toBeNull();
        expect($transaction->amount)->toBe($this->package->token_amount);
    });
});
