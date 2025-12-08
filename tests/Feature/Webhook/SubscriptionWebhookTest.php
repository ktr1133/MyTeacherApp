<?php

use App\Models\Group;
use App\Models\User;
use App\Services\Subscription\SubscriptionWebhookService;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Subscription;

/**
 * Subscription Webhook Feature Test
 * 
 * customer.subscription.updatedとcustomer.subscription.deletedの統合テスト
 * Stripe Webhook署名検証はCashierの実装に依存するため、Serviceを直接テスト
 */

beforeEach(function () {
    $this->service = app(SubscriptionWebhookService::class);
    
    // テスト用のユーザーとグループを作成
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create([
        'master_user_id' => $this->user->id,
        'stripe_id' => 'cus_test123',
        'subscription_active' => true,
        'subscription_plan' => 'family',
        'max_members' => 10,
    ]);
    
    $this->user->update(['group_id' => $this->group->id]);
    
    // Logモック
    Log::shouldReceive('info')->byDefault()->andReturnNull();
    Log::shouldReceive('error')->byDefault()->andReturnNull();
    Log::shouldReceive('warning')->byDefault()->andReturnNull();
});

describe('customer.subscription.updated 処理', function () {
    it('期間終了を検知してGroupsテーブルをリセット', function () {
        // Arrange
        $subscription = Subscription::factory()->create([
            'user_id' => $this->group->id,
            'stripe_id' => 'sub_test123',
            'stripe_status' => 'canceled',
            'ends_at' => now()->subDay(),
        ]);
        
        $payload = [
            'data' => [
                'object' => [
                    'id' => 'sub_test123',
                    'customer' => 'cus_test123',
                    'status' => 'canceled',
                    'current_period_end' => now()->subDay()->timestamp,
                    'metadata' => [
                        'group_id' => $this->group->id,
                        'plan' => 'family',
                    ],
                ],
            ],
        ];
        
        // Act
        $this->service->handleSubscriptionUpdated($payload);
        
        // Assert
        $this->group->refresh();
        expect($this->group->subscription_active)->toBeFalse();
        expect($this->group->subscription_plan)->toBeNull();
        expect($this->group->max_members)->toBe(6);
    });

    it('猶予期間中は通常の更新処理のみ実行', function () {
        // Arrange
        $subscription = Subscription::factory()->create([
            'user_id' => $this->group->id,
            'stripe_id' => 'sub_test456',
            'stripe_status' => 'canceled',
            'ends_at' => now()->addDay(),
        ]);
        
        $payload = [
            'data' => [
                'object' => [
                    'id' => 'sub_test456',
                    'customer' => 'cus_test123',
                    'status' => 'canceled',
                    'current_period_end' => now()->addDay()->timestamp, // 猶予期間中
                    'metadata' => [
                        'group_id' => $this->group->id,
                        'plan' => 'family',
                    ],
                ],
            ],
        ];
        
        // Act
        $this->service->handleSubscriptionUpdated($payload);
        
        // Assert
        $this->group->refresh();
        expect($this->group->subscription_active)->toBeFalse(); // statusがcanceledなので無効化
        expect($this->group->subscription_plan)->toBe('family'); // プランは維持
    });

    it('アクティブなサブスクリプションは更新のみ実行', function () {
        // Arrange
        $subscription = Subscription::factory()->create([
            'user_id' => $this->group->id,
            'stripe_id' => 'sub_test789',
            'stripe_status' => 'active',
            'ends_at' => null,
        ]);
        
        $payload = [
            'data' => [
                'object' => [
                    'id' => 'sub_test789',
                    'customer' => 'cus_test123',
                    'status' => 'active',
                    'current_period_end' => now()->addMonth()->timestamp,
                    'metadata' => [
                        'group_id' => $this->group->id,
                        'plan' => 'enterprise', // プラン変更
                    ],
                ],
            ],
        ];
        
        // Act
        $this->service->handleSubscriptionUpdated($payload);
        
        // Assert
        $this->group->refresh();
        expect($this->group->subscription_active)->toBeTrue();
        expect($this->group->subscription_plan)->toBe('enterprise');
        expect($this->group->max_members)->toBe(20);
    });
});

describe('customer.subscription.deleted 処理', function () {
    it('サブスクリプション削除時にGroupsテーブルをリセット', function () {
        // Arrange
        $subscription = Subscription::factory()->create([
            'user_id' => $this->group->id,
            'stripe_id' => 'sub_test_delete',
            'stripe_status' => 'canceled',
        ]);
        
        $payload = [
            'data' => [
                'object' => [
                    'id' => 'sub_test_delete',
                    'customer' => 'cus_test123',
                    'metadata' => [
                        'group_id' => $this->group->id,
                        'plan' => 'family',
                    ],
                ],
            ],
        ];
        
        // Act
        $this->service->handleSubscriptionDeleted($payload);
        
        // Assert
        $this->group->refresh();
        expect($this->group->subscription_active)->toBeFalse();
        expect($this->group->subscription_plan)->toBeNull();
        expect($this->group->max_members)->toBe(6);
    });
});

describe('Webhook 冪等性テスト', function () {
    it('期間終了検知を2回実行しても安全', function () {
        // Arrange
        $subscription = Subscription::factory()->create([
            'user_id' => $this->group->id,
            'stripe_id' => 'sub_idempotent',
            'stripe_status' => 'canceled',
            'ends_at' => now()->subDay(),
        ]);
        
        $payload = [
            'data' => [
                'object' => [
                    'id' => 'sub_idempotent',
                    'customer' => 'cus_test123',
                    'status' => 'canceled',
                    'current_period_end' => now()->subDay()->timestamp,
                    'metadata' => [
                        'group_id' => $this->group->id,
                        'plan' => 'family',
                    ],
                ],
            ],
        ];
        
        // Act（1回目）
        $this->service->handleSubscriptionUpdated($payload);
        
        $this->group->refresh();
        $firstState = [
            'active' => $this->group->subscription_active,
            'plan' => $this->group->subscription_plan,
            'max_members' => $this->group->max_members,
        ];
        
        // Act（2回目）
        $this->service->handleSubscriptionUpdated($payload);
        
        $this->group->refresh();
        $secondState = [
            'active' => $this->group->subscription_active,
            'plan' => $this->group->subscription_plan,
            'max_members' => $this->group->max_members,
        ];
        
        // Assert（状態変化なし）
        expect($firstState)->toBe($secondState);
        expect($this->group->subscription_active)->toBeFalse();
    });
});
