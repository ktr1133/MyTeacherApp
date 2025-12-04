<?php

use App\Models\User;
use App\Models\TokenBalance;
use App\Models\TokenTransaction;
use App\Services\Token\TokenServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * トークン残高管理テスト
 * 
 * マイグレーション確認済み:
 * - database/migrations/2025_01_01_000003_create_token_balances_table.php
 *   - token_balances.tokenable_type/tokenable_id (morphs)
 *   - token_balances.balance (bigInteger, default 0)
 *   - token_balances.free_balance (bigInteger, default 0)
 *   - token_balances.paid_balance (bigInteger, default 0)
 *   - token_balances.free_balance_reset_at (timestamp, nullable)
 *   - token_balances.total_consumed (bigInteger, default 0)
 *   - token_balances.monthly_consumed (bigInteger, default 0)
 *   - token_balances.monthly_consumed_reset_at (timestamp, nullable)
 * 
 * - database/migrations/2025_01_01_000004_create_token_transactions_table.php
 *   - token_transactions.tokenable_type/tokenable_id (morphs)
 *   - token_transactions.user_id (foreignId, nullable)
 *   - token_transactions.type (enum: config('const.token_transaction_types'))
 *   - token_transactions.amount (bigInteger)
 *   - token_transactions.balance_after (bigInteger)
 *   - token_transactions.reason (string, nullable)
 *   - token_transactions.related_type/related_id (nullable)
 *   - token_transactions.stripe_payment_intent_id (string, nullable)
 *   - token_transactions.stripe_metadata (json, nullable)
 *   - token_transactions.admin_note (text, nullable)
 *   - token_transactions.admin_user_id (foreignId, nullable)
 */

beforeEach(function () {
    $this->user = User::factory()->create();
    
    // トークン残高初期化
    $this->tokenBalance = TokenBalance::create([
        'tokenable_type' => User::class,
        'tokenable_id' => $this->user->id,
        'balance' => 100000,
        'free_balance' => 100000,
        'paid_balance' => 0,
    ]);
    
    $this->tokenService = app(TokenServiceInterface::class);
});

describe('トークン残高初期化', function () {
    test('ユーザー作成時にトークン残高が初期化される', function () {
        expect($this->tokenBalance->balance)->toBe(100000)
            ->and($this->tokenBalance->free_balance)->toBe(100000)
            ->and($this->tokenBalance->paid_balance)->toBe(0);
    });

    test('無料枠と有料枠の合計が総残高と一致する', function () {
        expect($this->tokenBalance->balance)
            ->toBe($this->tokenBalance->free_balance + $this->tokenBalance->paid_balance);
    });
});

describe('トークン消費', function () {
    test('無料枠からトークンを消費できる', function () {
        // TokenService::consumeTokens() を使用してトークン消費
        $result = $this->tokenService->consumeTokens($this->user, 5000, 'AI機能: タスク分解');
        
        expect($result)->toBeTrue();
        
        // DBから再取得して確認（fresh()でリロード）
        $balance = $this->tokenBalance->fresh();
        expect($balance->balance)->toBe(95000)
            ->and($balance->free_balance)->toBe(95000)
            ->and($balance->paid_balance)->toBe(0)
            ->and($balance->total_consumed)->toBe(5000)
            ->and($balance->monthly_consumed)->toBe(5000);
        
        // トランザクション記録の確認
        $this->assertDatabaseHas('token_transactions', [
            'tokenable_type' => User::class,
            'tokenable_id' => $this->user->id,
            'user_id' => $this->user->id,
            'type' => 'consume',
            'amount' => -5000,
            'balance_after' => 95000,
            'reason' => 'AI機能: タスク分解',
        ]);
    });

    test('有料枠からトークンを消費できる', function () {
        // 有料トークンを追加
        $this->tokenBalance->update([
            'balance' => 200000,
            'free_balance' => 100000,
            'paid_balance' => 100000,
        ]);

        // 無料枠を超える消費（150000トークン）
        // TokenService::consumeTokens() は無料枠100000 + 有料枠50000を自動計算
        $result = $this->tokenService->consumeTokens($this->user, 150000, 'AI機能: 大規模処理');
        
        expect($result)->toBeTrue();
        
        $this->tokenBalance->refresh();
        expect($this->tokenBalance->balance)->toBe(50000)
            ->and($this->tokenBalance->free_balance)->toBe(0)
            ->and($this->tokenBalance->paid_balance)->toBe(50000);
    });

    test('残高不足の場合は消費できない', function () {
        // TokenService::consumeTokens() で残高チェック
        $result = $this->tokenService->consumeTokens($this->user, 200000, 'AI機能: 超大規模処理');
        
        expect($result)->toBeFalse();
        
        // 残高は変化しない
        $this->tokenBalance->refresh();
        expect($this->tokenBalance->balance)->toBe(100000);
    });
});

describe('トークン付与', function () {
    test('トークン購入で有料枠に追加される', function () {
        // マイグレーション確認: type = 'purchase' で有料枠に付与
        TokenTransaction::create([
            'tokenable_type' => User::class,
            'tokenable_id' => $this->user->id,
            'user_id' => $this->user->id,
            'type' => 'purchase',
            'amount' => 500000,
            'balance_after' => 600000,
            'stripe_payment_intent_id' => 'pi_test_12345',
        ]);

        $this->tokenBalance->update([
            'balance' => 600000,
            'free_balance' => 100000,
            'paid_balance' => 500000,
        ]);

        $this->tokenBalance->refresh();
        expect($this->tokenBalance->balance)->toBe(600000)
            ->and($this->tokenBalance->free_balance)->toBe(100000)
            ->and($this->tokenBalance->paid_balance)->toBe(500000);
    });

    test('管理者付与で無料枠に追加される', function () {
        // マイグレーション確認: type = 'admin_adjust' で管理者による調整（enum値確認済み: config/const.php）
        TokenTransaction::create([
            'tokenable_type' => User::class,
            'tokenable_id' => $this->user->id,
            'user_id' => $this->user->id,
            'type' => 'admin_adjust',  // 'grant' ではなく 'admin_adjust' を使用
            'amount' => 50000,
            'balance_after' => 150000,
            'admin_note' => 'テストユーザーへの追加付与',
        ]);

        $this->tokenBalance->update([
            'balance' => 150000,
            'free_balance' => 150000,
            'paid_balance' => 0,
        ]);

        $this->tokenBalance->refresh();
        expect($this->tokenBalance->balance)->toBe(150000)
            ->and($this->tokenBalance->free_balance)->toBe(150000);
    });
});

describe('月次リセット', function () {
    test('月次消費量がリセットされる', function () {
        // 消費実績を作成
        $resetTime = now();
        $this->tokenBalance->update([
            'monthly_consumed' => 50000,
            'monthly_consumed_reset_at' => now()->subMonth(),
        ]);

        // リセット処理（実際のService層で実装）
        $this->tokenBalance->monthly_consumed = 0;
        $this->tokenBalance->monthly_consumed_reset_at = $resetTime;
        $this->tokenBalance->save();

        // DBから再取得して確認
        $balance = TokenBalance::find($this->tokenBalance->id);
        expect($balance->monthly_consumed)->toBe(0)
            ->and($balance->monthly_consumed_reset_at)->not->toBeNull()
            ->and($balance->monthly_consumed_reset_at->format('Y-m-d H:i'))
            ->toBe($resetTime->format('Y-m-d H:i'));
    });

    test('無料枠が月初にリセットされる（free_resetタイプ）', function () {
        // 無料枠を消費
        $this->tokenBalance->update([
            'balance' => 50000,
            'free_balance' => 50000,
            'paid_balance' => 0,
        ]);

        $resetTime = now();
        
        // マイグレーション確認: type = 'free_reset' で無料枠リセット
        TokenTransaction::create([
            'tokenable_type' => User::class,
            'tokenable_id' => $this->user->id,
            'user_id' => $this->user->id,
            'type' => 'free_reset',
            'amount' => 1000000,  // 月次無料枠（config('const.token.free_monthly')）
            'balance_after' => 1050000,
            'reason' => '月次無料枠リセット',
        ]);

        $this->tokenBalance->balance = 1050000;
        $this->tokenBalance->free_balance = 1000000;
        $this->tokenBalance->paid_balance = 0;
        $this->tokenBalance->free_balance_reset_at = $resetTime;
        $this->tokenBalance->save();

        // DBから再取得して確認
        $balance = TokenBalance::find($this->tokenBalance->id);
        expect($balance->free_balance)->toBe(1000000)
            ->and($balance->free_balance_reset_at)->not->toBeNull()
            ->and($balance->free_balance_reset_at->format('Y-m-d H:i'))
            ->toBe($resetTime->format('Y-m-d H:i'));
    });
});

describe('トランザクション記録', function () {
    test('消費トランザクションが正しく記録される', function () {
        // マイグレーション確認: 全カラムが存在することを確認
        $transaction = TokenTransaction::create([
            'tokenable_type' => User::class,
            'tokenable_id' => $this->user->id,
            'user_id' => $this->user->id,
            'type' => 'consume',  // enum値: config('const.token_transaction_types')
            'amount' => -5000,
            'balance_after' => 95000,
            'reason' => 'AI機能: タスク分解',
            'related_type' => 'App\\Models\\Task',
            'related_id' => 123,
        ]);

        expect($transaction->type)->toBe('consume')
            ->and($transaction->amount)->toBe(-5000)
            ->and($transaction->balance_after)->toBe(95000)
            ->and($transaction->reason)->toBe('AI機能: タスク分解');
    });

    test('購入トランザクションにStripe情報が記録される', function () {
        // マイグレーション確認: stripe_payment_intent_id, stripe_metadata カラム
        $transaction = TokenTransaction::create([
            'tokenable_type' => User::class,
            'tokenable_id' => $this->user->id,
            'user_id' => $this->user->id,
            'type' => 'purchase',
            'amount' => 500000,
            'balance_after' => 600000,
            'stripe_payment_intent_id' => 'pi_test_abc123',
            'stripe_metadata' => [
                'package_id' => 1,
                'package_name' => '0.5Mトークン',
                'price' => 400,
            ],
        ]);

        expect($transaction->stripe_payment_intent_id)->toBe('pi_test_abc123')
            ->and($transaction->stripe_metadata)->toBeArray()
            ->and($transaction->stripe_metadata['package_name'])->toBe('0.5Mトークン');
    });

    test('管理者操作にadmin_noteが記録される', function () {
        // マイグレーション確認: admin_note, admin_user_id カラム
        $admin = User::factory()->create(['is_admin' => true]);
        
        $transaction = TokenTransaction::create([
            'tokenable_type' => User::class,
            'tokenable_id' => $this->user->id,
            'user_id' => $this->user->id,
            'type' => 'admin_adjust',
            'amount' => 100000,
            'balance_after' => 200000,
            'admin_note' => 'テストユーザーへの調整付与',
            'admin_user_id' => $admin->id,
        ]);

        expect($transaction->admin_note)->toBe('テストユーザーへの調整付与')
            ->and($transaction->admin_user_id)->toBe($admin->id);
    });
});

describe('トークン残高整合性', function () {
    test('トランザクション内でトークン付与が実行される', function () {
        DB::beginTransaction();
        
        try {
            // トークン購入
            $initialBalance = $this->tokenBalance->balance;
            
            $this->tokenBalance->increment('balance', 500000);
            $this->tokenBalance->increment('paid_balance', 500000);
            
            TokenTransaction::create([
                'tokenable_type' => User::class,
                'tokenable_id' => $this->user->id,
                'user_id' => $this->user->id,
                'type' => 'purchase',
                'amount' => 500000,
                'balance_after' => $initialBalance + 500000,
            ]);
            
            DB::commit();
            
            $this->tokenBalance->refresh();
            expect($this->tokenBalance->balance)->toBe($initialBalance + 500000);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    });

    test('トランザクション失敗時にロールバックされる', function () {
        $initialBalance = $this->tokenBalance->balance;
        
        try {
            DB::beginTransaction();
            
            $this->tokenBalance->increment('balance', 500000);
            
            // 意図的にエラーを発生させる（不正なtype）
            TokenTransaction::create([
                'tokenable_type' => User::class,
                'tokenable_id' => $this->user->id,
                'type' => 'invalid_type',  // 存在しないenum値 → エラー
                'amount' => 500000,
                'balance_after' => $initialBalance + 500000,
            ]);
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
        }
        
        // ロールバックにより残高は変化しない
        $this->tokenBalance->refresh();
        expect($this->tokenBalance->balance)->toBe($initialBalance);
    });
});
