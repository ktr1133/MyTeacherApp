<?php

use App\Models\User;
use App\Models\Group;
use Laravel\Sanctum\Sanctum;

/**
 * サブスクリプション管理API Feature Test
 * 
 * Phase 2.B-6: サブスクリプション管理機能
 * 
 * テスト対象:
 * - GET /api/subscriptions/plans
 * - GET /api/subscriptions/current
 * - POST /api/subscriptions/checkout
 * - GET /api/subscriptions/invoices
 * - POST /api/subscriptions/update
 * - POST /api/subscriptions/cancel
 * - POST /api/subscriptions/billing-portal
 */

beforeEach(function () {
    // 親ユーザー（グループマスター）作成
    $this->user = User::factory()->create([
        'theme' => 'adult',
    ]);

    // グループ作成
    $this->group = Group::factory()->create([
        'master_user_id' => $this->user->id,
        'subscription_active' => false,
        'subscription_plan' => null,
    ]);

    $this->user->update(['group_id' => $this->group->id]);
});

test('プラン一覧を取得できる', function () {
    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/subscriptions/plans');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'plans' => [
                '*' => [
                    'name',
                    'displayName',
                    'description',
                    'price',
                    'maxMembers',
                    'features',
                    'stripePriceId',
                    'stripePlanName',
                ],
            ],
            'additional_member_price',
            'current_plan',
        ]);

    // plansが配列であることを確認
    expect($response->json('plans'))->toBeArray();
    expect(count($response->json('plans')))->toBeGreaterThanOrEqual(2);
    
    // 追加メンバー価格の確認
    expect($response->json('additional_member_price'))->toBe(150);
    
    // 現在のプラン（サブスク未加入なのでnull）
    expect($response->json('current_plan'))->toBeNull();
});

test('子どもテーマユーザーはプラン一覧を取得できない', function () {
    $childUser = User::factory()->create([
        'theme' => 'child',
        'group_id' => $this->group->id,
    ]);

    Sanctum::actingAs($childUser);

    $response = $this->getJson('/api/subscriptions/plans');

    $response->assertStatus(403)
        ->assertJson([
            'error' => 'サブスクリプション管理権限がありません。',
        ]);
});

test('サブスク未加入の場合はnullを返す', function () {
    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/subscriptions/current');

    $response->assertStatus(200)
        ->assertJson([
            'subscription' => null,
        ]);
});

test('Checkout Session作成時に無効なプランを指定するとエラー', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/subscriptions/checkout', [
        'plan' => 'invalid_plan',
    ]);

    $response->assertStatus(400)
        ->assertJsonStructure(['error']);
});

test('サブスク未加入の場合は請求履歴を取得できない', function () {
    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/subscriptions/invoices');

    $response->assertStatus(404)
        ->assertJson([
            'error' => '有効なサブスクリプションが見つかりません。',
        ]);
});

test('サブスク未加入の場合はプラン変更できない', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/subscriptions/update', [
        'new_plan' => 'enterprise',
    ]);

    $response->assertStatus(404)
        ->assertJson([
            'error' => '有効なサブスクリプションが見つかりません。',
        ]);
});

test('サブスク未加入の場合はキャンセルできない', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/subscriptions/cancel');

    $response->assertStatus(404)
        ->assertJson([
            'error' => '有効なサブスクリプションが見つかりません。',
        ]);
});

test('サブスク未加入の場合はBilling Portal URLを取得できない', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/subscriptions/billing-portal');

    $response->assertStatus(404)
        ->assertJson([
            'error' => '有効なサブスクリプションが見つかりません。',
        ]);
});

test('認証なしではアクセスできない', function () {
    $this->getJson('/api/subscriptions/plans')->assertStatus(401);
    $this->getJson('/api/subscriptions/current')->assertStatus(401);
    $this->postJson('/api/subscriptions/checkout', ['plan' => 'family'])->assertStatus(401);
    $this->getJson('/api/subscriptions/invoices')->assertStatus(401);
    $this->postJson('/api/subscriptions/update', ['new_plan' => 'enterprise'])->assertStatus(401);
    $this->postJson('/api/subscriptions/cancel')->assertStatus(401);
    $this->postJson('/api/subscriptions/billing-portal')->assertStatus(401);
});
