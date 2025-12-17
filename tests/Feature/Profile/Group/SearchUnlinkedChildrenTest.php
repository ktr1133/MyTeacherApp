<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Carbon;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

/**
 * 未紐付け子アカウント検索テスト (Web版)
 * 
 * Phase 8 - Task 5: 未紐付け子検索機能のIntegration Test
 * 
 * テスト対象:
 * - POST /profile/group/search-children
 * - SearchUnlinkedChildrenAction
 * 
 * 検証項目:
 * 1. parent_emailで未紐付け子を検索できる
 * 2. 検索結果が created_at降順で返却される
 * 3. 既にグループに所属している子は除外される
 * 4. is_minor=false のアカウントは除外される
 * 5. parent_emailが一致しない子は除外される
 * 6. 該当なしの場合は空配列を返す
 * 7. 認証が必要（未ログインは302）
 */

test('parent can search unlinked children by parent_email', function () {
    $parent = User::factory()->create(['email' => 'parent@example.com', 'is_minor' => false]);
    $parentEmail = 'parent@example.com';
    
    $child1 = User::factory()->create([
        'username' => 'child1',
        'parent_email' => $parentEmail,
        'is_minor' => true,
        'group_id' => null,
        'created_at' => Carbon::now()->subDays(2),
    ]);
    
    $child2 = User::factory()->create([
        'username' => 'child2',
        'parent_email' => $parentEmail,
        'is_minor' => true,
        'group_id' => null,
        'created_at' => Carbon::now()->subDays(1),
    ]);
    
    $response = actingAs($parent)->post(route('profile.group.search-children'), [
        'parent_email' => $parentEmail,
    ]);
    
    $response->assertRedirect();
    $response->assertSessionHas('children');
    
    $children = session('children');
    expect($children)->toHaveCount(2);
    expect($children->first()->username)->toBe('child2'); // 新しい順
    expect($children->last()->username)->toBe('child1');
});

test('search results are ordered by created_at descending', function () {
    $parent = User::factory()->create(['is_minor' => false]);
    $parentEmail = 'test@example.com';
    
    User::factory()->create([
        'username' => 'oldest',
        'parent_email' => $parentEmail,
        'is_minor' => true,
        'group_id' => null,
        'created_at' => Carbon::now()->subDays(3),
    ]);
    
    User::factory()->create([
        'username' => 'newest',
        'parent_email' => $parentEmail,
        'is_minor' => true,
        'group_id' => null,
        'created_at' => Carbon::now()->subDays(1),
    ]);
    
    $response = actingAs($parent)->post(route('profile.group.search-children'), [
        'parent_email' => $parentEmail,
    ]);
    
    $children = session('children');
    expect($children)->toHaveCount(2);
    expect($children->pluck('username')->toArray())->toBe(['newest', 'oldest']);
});

test('children already in group are excluded from search results', function () {
    $parent = User::factory()->create(['is_minor' => false]);
    $parentEmail = 'parent@example.com';
    
    // グループを作成
    $group = Group::create(['name' => 'test-group', 'master_user_id' => $parent->id]);
    
    User::factory()->create([
        'parent_email' => $parentEmail,
        'is_minor' => true,
        'group_id' => $group->id, // グループ所属
    ]);
    
    $childNotInGroup = User::factory()->create([
        'username' => 'unlinked',
        'parent_email' => $parentEmail,
        'is_minor' => true,
        'group_id' => null,
    ]);
    
    $response = actingAs($parent)->post(route('profile.group.search-children'), [
        'parent_email' => $parentEmail,
    ]);
    
    $children = session('children');
    expect($children)->toHaveCount(1);
    expect($children->first()->username)->toBe('unlinked');
});

test('non-minor accounts are excluded from search results', function () {
    $parent = User::factory()->create(['is_minor' => false]);
    $parentEmail = 'parent@example.com';
    
    User::factory()->create([
        'parent_email' => $parentEmail,
        'is_minor' => false, // 成人
        'group_id' => null,
    ]);
    
    $childAccount = User::factory()->create([
        'username' => 'childtest',
        'parent_email' => $parentEmail,
        'is_minor' => true,
        'group_id' => null,
    ]);
    
    $response = actingAs($parent)->post(route('profile.group.search-children'), [
        'parent_email' => $parentEmail,
    ]);
    
    $children = session('children');
    expect($children)->toHaveCount(1);
    expect($children->first()->username)->toBe('childtest');
});

test('children with different parent_email are excluded', function () {
    $parent = User::factory()->create(['is_minor' => false]);
    $parentEmail = 'parent@example.com';
    
    $matchingChild = User::factory()->create([
        'username' => 'matching',
        'parent_email' => $parentEmail,
        'is_minor' => true,
        'group_id' => null,
    ]);
    
    User::factory()->create([
        'parent_email' => 'other@example.com',
        'is_minor' => true,
        'group_id' => null,
    ]);
    
    $response = actingAs($parent)->post(route('profile.group.search-children'), [
        'parent_email' => $parentEmail,
    ]);
    
    $children = session('children');
    expect($children)->toHaveCount(1);
    expect($children->first()->username)->toBe('matching');
});

test('search returns empty array when no matching children found', function () {
    $parent = User::factory()->create(['is_minor' => false]);
    
    $response = actingAs($parent)->post(route('profile.group.search-children'), [
        'parent_email' => 'nonexistent@example.com',
    ]);
    
    $response->assertRedirect();
    $children = session('children');
    expect($children)->toHaveCount(0);
});

test('unauthenticated user cannot search unlinked children', function () {
    $response = post(route('profile.group.search-children'), [
        'parent_email' => 'test@example.com',
    ]);
    
    $response->assertRedirect(route('login'));
});
