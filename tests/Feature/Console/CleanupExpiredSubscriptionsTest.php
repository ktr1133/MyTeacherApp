<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Cashier\Subscription;

/**
 * CleanupExpiredSubscriptions Integration Test
 * 
 * subscription:cleanup-expired コマンドの統合テスト
 */

beforeEach(function () {
    // テスト用のユーザーとグループを作成
    $this->user = User::factory()->create();
});

describe('subscription:cleanup-expired コマンド', function () {
    it('期間終了したサブスクリプションをリセット', function () {
        // Arrange
        $group1 = Group::factory()->create([
            'master_user_id' => $this->user->id,
            'subscription_active' => true,
            'subscription_plan' => 'family',
            'max_members' => 10,
        ]);
        
        $subscription1 = Subscription::factory()->create([
            'user_id' => $group1->id,
            'stripe_id' => 'sub_expired1',
            'stripe_status' => 'canceled',
            'ends_at' => now()->subDay(), // 期間終了
        ]);
        
        $group2 = Group::factory()->create([
            'master_user_id' => $this->user->id,
            'subscription_active' => true,
            'subscription_plan' => 'enterprise',
            'max_members' => 20,
        ]);
        
        $subscription2 = Subscription::factory()->create([
            'user_id' => $group2->id,
            'stripe_id' => 'sub_active',
            'stripe_status' => 'canceled',
            'ends_at' => now()->addDay(), // 猶予期間中
        ]);
        
        // Act
        Artisan::call('subscription:cleanup-expired');
        
        // Assert
        $group1->refresh();
        $group2->refresh();
        
        // group1: リセットされる
        expect($group1->subscription_active)->toBeFalse();
        expect($group1->subscription_plan)->toBeNull();
        expect($group1->max_members)->toBe(6);
        expect($group1->max_groups)->toBe(1);
        
        // group2: 変更なし（猶予期間中）
        expect($group2->subscription_active)->toBeTrue();
        expect($group2->subscription_plan)->toBe('enterprise');
    });

    it('冪等性がある（2回実行しても安全）', function () {
        // Arrange
        $group = Group::factory()->create([
            'master_user_id' => $this->user->id,
            'subscription_active' => true,
            'subscription_plan' => 'family',
            'max_members' => 10,
        ]);
        
        $subscription = Subscription::factory()->create([
            'user_id' => $group->id,
            'stripe_id' => 'sub_idempotent',
            'stripe_status' => 'canceled',
            'ends_at' => now()->subDay(),
        ]);
        
        // Act（1回目）
        Artisan::call('subscription:cleanup-expired');
        $group->refresh();
        $firstState = [
            'active' => $group->subscription_active,
            'plan' => $group->subscription_plan,
            'max_members' => $group->max_members,
        ];
        
        // Act（2回目）
        Artisan::call('subscription:cleanup-expired');
        $group->refresh();
        $secondState = [
            'active' => $group->subscription_active,
            'plan' => $group->subscription_plan,
            'max_members' => $group->max_members,
        ];
        
        // Assert（状態変化なし）
        expect($firstState)->toBe($secondState);
        expect($group->subscription_active)->toBeFalse();
        expect($group->subscription_plan)->toBeNull();
    });

    it('複数の期間終了サブスクリプションを一括処理', function () {
        // Arrange
        $groups = [];
        $subscriptions = [];
        
        for ($i = 0; $i < 5; $i++) {
            $groups[$i] = Group::factory()->create([
                'master_user_id' => $this->user->id,
                'subscription_active' => true,
                'subscription_plan' => 'family',
                'max_members' => 10,
            ]);
            
            $subscriptions[$i] = Subscription::factory()->create([
                'user_id' => $groups[$i]->id,
                'stripe_id' => "sub_batch_{$i}",
                'stripe_status' => 'canceled',
                'ends_at' => now()->subDays($i + 1),
            ]);
        }
        
        // Act
        Artisan::call('subscription:cleanup-expired');
        
        // Assert
        foreach ($groups as $group) {
            $group->refresh();
            expect($group->subscription_active)->toBeFalse();
            expect($group->subscription_plan)->toBeNull();
            expect($group->max_members)->toBe(6);
        }
    });

    it('期間終了サブスクリプションが0件の場合も正常終了', function () {
        // Arrange（期間終了なし）
        $group = Group::factory()->create([
            'master_user_id' => $this->user->id,
            'subscription_active' => true,
            'subscription_plan' => 'family',
        ]);
        
        $subscription = Subscription::factory()->create([
            'user_id' => $group->id,
            'stripe_id' => 'sub_active',
            'stripe_status' => 'active',
            'ends_at' => null,
        ]);
        
        // Act
        $exitCode = Artisan::call('subscription:cleanup-expired');
        
        // Assert
        expect($exitCode)->toBe(0); // Command::SUCCESS
        $group->refresh();
        expect($group->subscription_active)->toBeTrue(); // 変更なし
    });

    it('Groupが見つからない場合はスキップして続行', function () {
        // Arrange
        $group = Group::factory()->create([
            'master_user_id' => $this->user->id,
            'subscription_active' => true,
            'subscription_plan' => 'family',
        ]);
        
        // Groupを削除してから、Subscriptionを残す（異常データ）
        $groupId = $group->id;
        $subscription = Subscription::factory()->create([
            'user_id' => $groupId,
            'stripe_id' => 'sub_orphan',
            'stripe_status' => 'canceled',
            'ends_at' => now()->subDay(),
        ]);
        
        $group->delete();
        
        // 正常なGroup/Subscriptionも作成
        $normalGroup = Group::factory()->create([
            'master_user_id' => $this->user->id,
            'subscription_active' => true,
            'subscription_plan' => 'enterprise',
        ]);
        
        $normalSubscription = Subscription::factory()->create([
            'user_id' => $normalGroup->id,
            'stripe_id' => 'sub_normal',
            'stripe_status' => 'canceled',
            'ends_at' => now()->subDay(),
        ]);
        
        // Act
        $exitCode = Artisan::call('subscription:cleanup-expired');
        
        // Assert
        expect($exitCode)->toBe(0); // 正常終了
        $normalGroup->refresh();
        expect($normalGroup->subscription_active)->toBeFalse(); // 正常なGroupはリセット
    });
});
