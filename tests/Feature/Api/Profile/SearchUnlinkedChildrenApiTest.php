<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Carbon;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

/**
 * 未紐付け子アカウント検索API テスト (Mobile版)
 * 
 * Phase 8 - Task 5: 未紐付け子検索機能のIntegration Test (API)
 * 
 * テスト対象:
 * - POST /api/profile/group/search-children
 * - SearchUnlinkedChildrenApiAction
 * 
 * 検証項目:
 * 1. parent_emailで未紐付け子を検索してJSON返却
 * 2. レスポンス形式の検証 (success, message, data構造)
 * 3. 子アカウント情報の項目確認
 * 4. 検索結果が created_at降順
 * 5. 既にグループに所属している子は除外
 * 6. 該当なしの場合は空配列
 * 7. 認証が必要（未ログインは401）
 */

test('parent can search unlinked children by parent_email via API', function () {
    $parent = User::factory()->create(['email' => 'parent@example.com', 'is_minor' => false]);
    $parentEmail = 'parent@example.com';
    
    $child1 = User::factory()->create([
        'username' => 'child1',
        'name' => 'Child One',
        'parent_email' => $parentEmail,
        'is_minor' => true,
        'group_id' => null,
        'created_at' => Carbon::now()->subDays(2),
    ]);
    
    $child2 = User::factory()->create([
        'username' => 'child2',
        'name' => 'Child Two',
        'parent_email' => $parentEmail,
        'is_minor' => true,
        'group_id' => null,
        'created_at' => Carbon::now()->subDays(1),
    ]);
    
    $response = actingAs($parent, 'sanctum')->postJson('/api/profile/group/search-children', [
        'parent_email' => $parentEmail,
    ]);
    
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '未紐付けの子アカウントが見つかりました。',
    ]);
    
    $data = $response->json('data');
    expect($data['count'])->toBe(2);
    expect($data['children'])->toHaveCount(2);
    expect($data['children'][0]['username'])->toBe('child2'); // 新しい順
});

test('API response includes all required child account fields', function () {
    $parent = User::factory()->create(['is_minor' => false]);
    $parentEmail = 'test@example.com';
    
    $child = User::factory()->create([
        'username' => 'testchild',
        'name' => 'Test Child',
        'email' => 'child@example.com',
        'parent_email' => $parentEmail,
        'is_minor' => true,
        'group_id' => null,
    ]);
    
    $response = actingAs($parent, 'sanctum')->postJson('/api/profile/group/search-children', [
        'parent_email' => $parentEmail,
    ]);
    
    $children = $response->json('data.children');
    expect($children[0])->toHaveKeys(['id', 'username', 'name', 'email', 'created_at', 'is_minor']);
    expect($children[0]['username'])->toBe('testchild');
    expect($children[0]['is_minor'])->toBeTrue();
});

test('API returns children ordered by created_at descending', function () {
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
    
    $response = actingAs($parent, 'sanctum')->postJson('/api/profile/group/search-children', [
        'parent_email' => $parentEmail,
    ]);
    
    $children = $response->json('data.children');
    expect($children)->toHaveCount(2);
    expect($children[0]['username'])->toBe('newest');
    expect($children[1]['username'])->toBe('oldest');
});

test('API excludes children already in group', function () {
    $parent = User::factory()->create(['is_minor' => false]);
    $parentEmail = 'parent@example.com';
    
    // グループを作成
    $group = Group::create(['name' => 'test-api-group', 'master_user_id' => $parent->id]);
    
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
    
    $response = actingAs($parent, 'sanctum')->postJson('/api/profile/group/search-children', [
        'parent_email' => $parentEmail,
    ]);
    
    $children = $response->json('data.children');
    expect($children)->toHaveCount(1);
    expect($children[0]['username'])->toBe('unlinked');
});

test('API returns empty array when no matching children found', function () {
    $parent = User::factory()->create(['is_minor' => false]);
    
    $response = actingAs($parent, 'sanctum')->postJson('/api/profile/group/search-children', [
        'parent_email' => 'nonexistent@example.com',
    ]);
    
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '該当する子アカウントが見つかりませんでした。',
    ]);
    
    expect($response->json('data.children'))->toHaveCount(0);
    expect($response->json('data.count'))->toBe(0);
});

test('API returns 401 when unauthenticated', function () {
    $response = postJson('/api/profile/group/search-children', [
        'parent_email' => 'test@example.com',
    ]);
    
    $response->assertStatus(401);
});
