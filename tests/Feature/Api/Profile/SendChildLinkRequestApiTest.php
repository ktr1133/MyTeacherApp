<?php

use App\Models\User;
use App\Models\Group;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SendPushNotificationJob;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;
use function Pest\Laravel\assertDatabaseHas;

/**
 * 紐付けリクエスト送信API テスト (Mobile版)
 * 
 * Phase 8 - Task 6: 紐付けリクエスト送信機能のIntegration Test (API)
 * 
 * テスト対象:
 * - POST /api/profile/group/send-link-request
 * - SendChildLinkRequestApiAction
 * 
 * 検証項目:
 * 1. APIで保護者から子へ紐付けリクエストを送信できる
 * 2. レスポンス形式の検証 (success, message, data構造)
 * 3. 通知テンプレート作成の確認
 * 4. 子が既にグループ所属の場合は400エラー
 * 5. 保護者がグループ未所属の場合は400エラー
 * 6. 認証が必要（未ログインは401）
 * 7. サーバーエラー時のエラーハンドリング
 */

test('parent can send link request to unlinked child via API', function () {
    Queue::fake();
    
    $group = Group::factory()->create(['name' => 'API Test Family']);
    
    $parent = User::factory()->create([
        'name' => 'API Parent',
        'username' => 'apiparent',
        'is_minor' => false,
        'group_id' => $group->id,
    ]);
    
    $child = User::factory()->create([
        'username' => 'apichild',
        'is_minor' => true,
        'group_id' => null,
    ]);
    
    $response = actingAs($parent, 'sanctum')->postJson('/api/profile/group/send-link-request', [
        'child_user_id' => $child->id,
    ]);
    
    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => 'お子様に紐付けリクエストを送信しました。',
    ]);
    
    // レスポンスデータの検証
    $data = $response->json('data');
    expect($data)->toHaveKey('notification_id');
    expect($data)->toHaveKey('child_user');
    
    expect($data['child_user']['username'])->toBe('apichild');
    
    // 通知テンプレート作成確認
    assertDatabaseHas('notification_templates', [
        'sender_id' => $parent->id,
        'type' => 'parent_link_request',
        'priority' => 'important',
    ]);
    
    $notification = NotificationTemplate::where('type', 'parent_link_request')
        ->where('sender_id', $parent->id)
        ->first();
    
    expect($notification->data['parent_user_id'])->toBe($parent->id);
    expect($notification->data['group_id'])->toBe($group->id);
    expect($notification->data['group_name'])->toBe('API Test Family');
    
    // Push通知ジョブ確認
    Queue::assertPushed(SendPushNotificationJob::class);
});

test('API returns notification details in response', function () {
    Queue::fake();
    
    $group = Group::factory()->create(['name' => 'Response Test']);
    $parent = User::factory()->create(['is_minor' => false, 'group_id' => $group->id]);
    $child = User::factory()->create(['username' => 'childtest', 'is_minor' => true, 'group_id' => null]);
    
    $response = actingAs($parent, 'sanctum')->postJson('/api/profile/group/send-link-request', [
        'child_user_id' => $child->id,
    ]);
    
    $data = $response->json('data');
    
    // 通知IDが返却される
    expect($data['notification_id'])->toBeInt();
    
    // 子アカウント情報
    expect($data['child_user'])->toHaveKeys(['id', 'username', 'name']);
    expect($data['child_user']['username'])->toBe('childtest');
});

test('API returns 400 when child already in group', function () {
    Queue::fake();
    
    $parentGroup = Group::factory()->create();
    $childGroup = Group::factory()->create();
    
    $parent = User::factory()->create(['is_minor' => false, 'group_id' => $parentGroup->id]);
    $child = User::factory()->create(['is_minor' => true, 'group_id' => $childGroup->id]);
    
    $response = actingAs($parent, 'sanctum')->postJson('/api/profile/group/send-link-request', [
        'child_user_id' => $child->id,
    ]);
    
    $response->assertStatus(400);
    $response->assertJson([
        'success' => false,
        'message' => 'お子様は既に別のグループに所属しているため、紐付けリクエストを送信できません。',
    ]);
    
    $response->assertJsonStructure([
        'success',
        'message',
        'errors' => ['child_user_id'],
    ]);
    
    // 通知は作成されない
    expect(NotificationTemplate::where('type', 'parent_link_request')->count())->toBe(0);
});

test('API returns 400 when parent not in group', function () {
    Queue::fake();
    
    $parent = User::factory()->create(['is_minor' => false, 'group_id' => null]);
    $child = User::factory()->create(['is_minor' => true, 'group_id' => null]);
    
    $response = actingAs($parent, 'sanctum')->postJson('/api/profile/group/send-link-request', [
        'child_user_id' => $child->id,
    ]);
    
    $response->assertStatus(400);
    $response->assertJson([
        'success' => false,
        'message' => 'グループに所属していないため、紐付けリクエストを送信できません。先にグループを作成してください。',
    ]);
    
    $response->assertJsonStructure([
        'errors' => ['group_id'],
    ]);
});

test('API returns 401 when unauthenticated', function () {
    $child = User::factory()->create(['is_minor' => true]);
    
    $response = postJson('/api/profile/group/send-link-request', [
        'child_user_id' => $child->id,
    ]);
    
    $response->assertStatus(401);
});

test('API handles invalid child_user_id gracefully', function () {
    Queue::fake();
    
    $group = Group::factory()->create();
    $parent = User::factory()->create(['is_minor' => false, 'group_id' => $group->id]);
    
    $response = actingAs($parent, 'sanctum')->postJson('/api/profile/group/send-link-request', [
        'child_user_id' => 99999, // 存在しないID
    ]);
    
    // バリデーションエラー（422）
    $response->assertStatus(422);
    $response->assertJson([
        'message' => '指定された子アカウントが見つかりません。',
    ]);
    
    $response->assertJsonStructure([
        'message',
        'errors' => ['child_user_id'],
    ]);
});
