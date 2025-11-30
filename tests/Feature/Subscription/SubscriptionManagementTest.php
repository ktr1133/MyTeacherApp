<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;

uses(RefreshDatabase::class);

/**
 * サブスクリプション管理画面のテスト
 */
describe('Subscription Management', function () {
    
    test('管理画面にアクセスできる（サブスクリプションなし）', function () {
        // グループ管理者作成
        $user = User::factory()->create();
        $group = Group::factory()->create([
            'master_user_id' => $user->id,
        ]);
        $user->update(['group_id' => $group->id]);

        // 管理画面にアクセス
        $response = $this->actingAs($user)->get(route('subscriptions.manage'));

        $response->assertStatus(200);
        $response->assertSee('現在、有効なサブスクリプションがありません。');
    });

    test('管理画面にアクセスできる（サブスクリプションあり）', function () {
        // グループ管理者作成
        $user = User::factory()->create();
        $group = Group::factory()->create([
            'master_user_id' => $user->id,
            'subscription_active' => true,
            'subscription_plan' => 'family',
            'stripe_id' => 'cus_test123',
        ]);
        $user->update(['group_id' => $group->id]);

        // サブスクリプション作成（モック）
        Subscription::factory()->create([
            'user_id' => $user->id,
            'name' => 'default',
            'stripe_id' => 'sub_test123',
            'stripe_status' => 'active',
            'stripe_price' => config('const.stripe.subscription_plans.family.price_id'),
            'quantity' => 1,
        ]);

        // 管理画面にアクセス
        $response = $this->actingAs($user)->get(route('subscriptions.manage'));

        $response->assertStatus(200);
        $response->assertSee('現在のサブスクリプション');
    });

    test('グループに所属していない場合はエラー', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('subscriptions.manage'));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('error');
    });

    test('管理権限がない場合は403エラー', function () {
        // グループ管理者作成
        $admin = User::factory()->create();
        $group = Group::factory()->create([
            'master_user_id' => $admin->id,
        ]);

        // 一般メンバー作成
        $member = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => false,
        ]);

        // 一般メンバーでアクセス
        $response = $this->actingAs($member)->get(route('subscriptions.manage'));

        $response->assertStatus(403);
    });

    test('編集権限を持つユーザーはアクセスできる', function () {
        // グループ管理者作成
        $admin = User::factory()->create();
        $group = Group::factory()->create([
            'master_user_id' => $admin->id,
        ]);

        // 編集権限を持つメンバー作成
        $editor = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => true,
        ]);

        // 編集権限を持つメンバーでアクセス
        $response = $this->actingAs($editor)->get(route('subscriptions.manage'));

        $response->assertStatus(200);
    });

    test('サブスクリプションをキャンセルできる（期間終了時）', function () {
        // グループ管理者作成
        $user = User::factory()->create();
        $group = Group::factory()->create([
            'master_user_id' => $user->id,
            'subscription_active' => true,
            'subscription_plan' => 'family',
            'stripe_id' => 'cus_test123',
        ]);
        $user->update(['group_id' => $group->id]);

        // サブスクリプション作成（モック）
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'name' => 'default',
            'stripe_id' => 'sub_test123',
            'stripe_status' => 'active',
            'stripe_price' => config('const.stripe.subscription_plans.family.price_id'),
            'quantity' => 1,
        ]);

        // Stripe APIモック（キャンセル成功）
        $this->mock(\Laravel\Cashier\Subscription::class, function ($mock) {
            $mock->shouldReceive('cancel')->once()->andReturnTrue();
        });

        // キャンセルリクエスト
        $response = $this->actingAs($user)->post(route('subscriptions.cancel.subscription'));

        $response->assertStatus(302);
        $response->assertSessionHas('success');
    });

    test('サブスクリプションを即座にキャンセルできる', function () {
        // グループ管理者作成
        $user = User::factory()->create();
        $group = Group::factory()->create([
            'master_user_id' => $user->id,
            'subscription_active' => true,
            'subscription_plan' => 'family',
            'stripe_id' => 'cus_test123',
        ]);
        $user->update(['group_id' => $group->id]);

        // サブスクリプション作成（モック）
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'name' => 'default',
            'stripe_id' => 'sub_test123',
            'stripe_status' => 'active',
            'stripe_price' => config('const.stripe.subscription_plans.family.price_id'),
            'quantity' => 1,
        ]);

        // Stripe APIモック（即時キャンセル成功）
        $this->mock(\Laravel\Cashier\Subscription::class, function ($mock) {
            $mock->shouldReceive('cancelNow')->once()->andReturnTrue();
        });

        // 即時キャンセルリクエスト
        $response = $this->actingAs($user)->post(route('subscriptions.cancel.subscription'), [
            'immediately' => true,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');
    });

    test('Billing Portalにリダイレクトできる', function () {
        // グループ管理者作成
        $user = User::factory()->create();
        $group = Group::factory()->create([
            'master_user_id' => $user->id,
            'subscription_active' => true,
            'subscription_plan' => 'family',
            'stripe_id' => 'cus_test123',
        ]);
        $user->update(['group_id' => $group->id]);

        // Stripe APIモック（Billing Portal URL取得）
        $this->mock(\App\Repositories\Subscription\SubscriptionRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('createBillingPortalSession')
                ->once()
                ->andReturn('https://billing.stripe.com/session/test123');
        });

        // Billing Portalへリダイレクト
        $response = $this->actingAs($user)->get(route('subscriptions.billing-portal'));

        $response->assertStatus(302);
        $response->assertRedirect('https://billing.stripe.com/session/test123');
    });

    test('プラン変更バリデーション: プラン必須', function () {
        $user = User::factory()->create();
        $group = Group::factory()->create([
            'master_user_id' => $user->id,
            'subscription_active' => true,
            'stripe_id' => 'cus_test123',
        ]);
        $user->update(['group_id' => $group->id]);

        $response = $this->actingAs($user)->post(route('subscriptions.update'), [
            // plan なし
        ]);

        $response->assertSessionHasErrors('plan');
    });

    test('プラン変更バリデーション: 無効なプラン', function () {
        $user = User::factory()->create();
        $group = Group::factory()->create([
            'master_user_id' => $user->id,
            'subscription_active' => true,
            'stripe_id' => 'cus_test123',
        ]);
        $user->update(['group_id' => $group->id]);

        $response = $this->actingAs($user)->post(route('subscriptions.update'), [
            'plan' => 'invalid_plan',
        ]);

        $response->assertSessionHasErrors('plan');
    });

    test('プラン変更バリデーション: 追加メンバー数は整数', function () {
        $user = User::factory()->create();
        $group = Group::factory()->create([
            'master_user_id' => $user->id,
            'subscription_active' => true,
            'stripe_id' => 'cus_test123',
        ]);
        $user->update(['group_id' => $group->id]);

        $response = $this->actingAs($user)->post(route('subscriptions.update'), [
            'plan' => 'enterprise',
            'additional_members' => 'abc',
        ]);

        $response->assertSessionHasErrors('additional_members');
    });
});
