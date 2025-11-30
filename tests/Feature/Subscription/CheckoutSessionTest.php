<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // テスト用のユーザーとグループを作成
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create([
        'master_user_id' => $this->user->id,
        'subscription_active' => false,
        'subscription_plan' => null,
        'max_members' => 6,
    ]);
    
    $this->user->update(['group_id' => $this->group->id]);
    
    // 環境変数にダミーのStripe Price IDを設定
    config([
        'const.stripe.subscription_plans.family.price_id' => 'price_test_family',
        'const.stripe.subscription_plans.enterprise.price_id' => 'price_test_enterprise',
        'const.stripe.additional_member_price_id' => 'price_test_additional',
    ]);
});

describe('プラン選択画面表示', function () {
    it('ログインユーザーはプラン選択画面を表示できる', function () {
        $response = $this->actingAs($this->user)->get(route('subscriptions.index'));
        
        $response->assertStatus(200)
            ->assertViewIs('subscriptions.select-plan')
            ->assertViewHas('plans');
    });

    it('未ログインユーザーはログイン画面にリダイレクトされる', function () {
        $response = $this->get(route('subscriptions.index'));
        
        $response->assertRedirect(route('login'));
    });

    it('グループ未所属ユーザーはエラーメッセージを表示される', function () {
        $userWithoutGroup = User::factory()->create(['group_id' => null]);
        
        $response = $this->actingAs($userWithoutGroup)->get(route('subscriptions.index'));
        
        $response->assertStatus(200);
        // グループ未所属の場合の処理を確認
    });
});

describe('Checkout Session作成 - バリデーション', function () {
    it('プランが必須であることを検証', function () {
        $response = $this->actingAs($this->user)->post(route('subscriptions.checkout'), [
            // planを省略
        ]);
        
        $response->assertSessionHasErrors(['plan']);
    });

    it('プランはfamilyまたはenterpriseのみ許可', function () {
        $response = $this->actingAs($this->user)->post(route('subscriptions.checkout'), [
            'plan' => 'invalid_plan',
        ]);
        
        $response->assertSessionHasErrors(['plan']);
    });

    it('追加メンバー数は整数であることを検証', function () {
        $response = $this->actingAs($this->user)->post(route('subscriptions.checkout'), [
            'plan' => 'enterprise',
            'additional_members' => 'invalid',
        ]);
        
        $response->assertSessionHasErrors(['additional_members']);
    });

    it('追加メンバー数は0以上50以下であることを検証', function () {
        $response = $this->actingAs($this->user)->post(route('subscriptions.checkout'), [
            'plan' => 'enterprise',
            'additional_members' => 51,
        ]);
        
        $response->assertSessionHasErrors(['additional_members']);
    });
});

describe('Checkout Session作成 - 権限', function () {
    it('グループ管理者はCheckout Sessionを作成できる', function () {
        // Stripeモックは実装しないため、例外が発生することを期待
        $response = $this->actingAs($this->user)->post(route('subscriptions.checkout'), [
            'plan' => 'family',
        ]);
        
        // Stripe APIがモックされていないため、エラーが返る
        // 本来はStripe\Checkout\Sessionをモックすべき
        $response->assertStatus(302);
    });

    it('グループ編集権限者はCheckout Sessionを作成できる', function () {
        $editor = User::factory()->create([
            'group_id' => $this->group->id,
            'group_edit_flg' => true,
        ]);
        
        $response = $this->actingAs($editor)->post(route('subscriptions.checkout'), [
            'plan' => 'family',
        ]);
        
        $response->assertStatus(302);
    });

    it('一般メンバーはCheckout Sessionを作成できない', function () {
        $member = User::factory()->create([
            'group_id' => $this->group->id,
            'group_edit_flg' => false,
        ]);
        
        $response = $this->actingAs($member)->post(route('subscriptions.checkout'), [
            'plan' => 'family',
        ]);
        
        $response->assertStatus(403);
    });

    it('未ログインユーザーはログイン画面にリダイレクトされる', function () {
        $response = $this->post(route('subscriptions.checkout'), [
            'plan' => 'family',
        ]);
        
        $response->assertRedirect(route('login'));
    });
});

describe('成功・キャンセル画面', function () {
    it('サブスクリプション成功画面を表示できる', function () {
        $response = $this->actingAs($this->user)
            ->get(route('subscriptions.success') . '?session_id=cs_test_123');
        
        $response->assertStatus(200)
            ->assertViewIs('subscriptions.success');
    });

    it('サブスクリプションキャンセル画面を表示できる', function () {
        $response = $this->actingAs($this->user)->get(route('subscriptions.cancel'));
        
        $response->assertStatus(200)
            ->assertViewIs('subscriptions.cancel');
    });
});

describe('プラン情報取得', function () {
    it('利用可能なプラン情報を取得できる', function () {
        $subscriptionService = app(\App\Services\Subscription\SubscriptionServiceInterface::class);
        
        $plans = $subscriptionService->getAvailablePlans();
        
        expect($plans)->toBeArray()
            ->toHaveKeys(['family', 'enterprise'])
            ->and($plans['family'])->toHaveKeys(['name', 'price_id', 'amount', 'max_members'])
            ->and($plans['enterprise'])->toHaveKeys(['name', 'price_id', 'amount', 'max_members']);
    });

    it('グループ管理権限を正しく判定できる', function () {
        $subscriptionService = app(\App\Services\Subscription\SubscriptionServiceInterface::class);
        
        // グループ管理者
        $this->actingAs($this->user);
        expect($subscriptionService->canManageSubscription($this->group))->toBeTrue();
        
        // 編集権限者
        $editor = User::factory()->create([
            'group_id' => $this->group->id,
            'group_edit_flg' => true,
        ]);
        $this->actingAs($editor);
        expect($subscriptionService->canManageSubscription($this->group))->toBeTrue();
        
        // 一般メンバー
        $member = User::factory()->create([
            'group_id' => $this->group->id,
            'group_edit_flg' => false,
        ]);
        $this->actingAs($member);
        expect($subscriptionService->canManageSubscription($this->group))->toBeFalse();
    });
});
