<?php

use App\Models\User;
use App\Models\Group;
use App\Models\NotificationTemplate;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SendPushNotificationJob;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;
use function Pest\Laravel\assertDatabaseHas;

/**
 * 親子紐付け承認API テスト (Mobile版)
 * 
 * Phase 8 - Task 7: 親子紐付け承認機能のIntegration Test (API)
 * 
 * テスト対象:
 * - POST /api/notifications/{notification_template_id}/approve-parent-link
 * - ApproveParentLinkApiAction
 * 
 * 検証項目:
 * 1. APIで子が紐付けリクエストを承認できる
 * 2. レスポンス形式の検証 (success, message, data)
 * 3. child.parent_user_id と group_id 更新確認
 * 4. 保護者に承認通知が送信される
 * 5. 子が既にグループ所属の場合は400エラー
 * 6. 無効な通知種別の場合は400エラー
 * 7. 認証が必要（未ログインは401）
 */

test('child can approve parent link request via API', function () {
    Queue::fake();
    
    $group = Group::factory()->create(['name' => 'API Approval Test']);
    $parent = User::factory()->create([
        'name' => 'API Parent',
        'username' => 'apiparent',
        'is_minor' => false,
        'group_id' => $group->id,
    ]);
    
    $child = User::factory()->create([
        'username' => 'apichild',
        'is_minor' => true,
        'parent_user_id' => null,
        'group_id' => null,
    ]);
    
    $notification = NotificationTemplate::create([
        'sender_id' => $parent->id,
        'source' => 'system',
        'type' => 'parent_link_request',
        'priority' => 'important',
        'title' => '保護者アカウントとの紐付けリクエスト',
        'message' => 'Test API message',
        'data' => [
            'parent_user_id' => $parent->id,
            'parent_name' => $parent->name,
            'group_id' => $group->id,
            'group_name' => $group->name,
        ],
        'target_type' => 'users',
        'target_ids' => [$child->id],
        'publish_at' => now(),
    ]);
    
    UserNotification::create([
        'user_id' => $child->id,
        'notification_template_id' => $notification->id,
        'is_read' => false,
    ]);
    
    $response = actingAs($child, 'sanctum')->postJson("/api/notifications/{$notification->id}/approve-parent-link");
    
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => '紐付けが完了しました。保護者アカウントと連携されました。',
    ]);
    
    // レスポンスデータの検証
    $data = $response->json('data');
    expect($data)->toHaveKey('child_user');
    expect($data)->toHaveKey('parent_user');
    expect($data)->toHaveKey('group');
    
    expect($data['child_user']['username'])->toBe('apichild');
    expect($data['parent_user']['username'])->toBe('apiparent');
    expect($data['group']['id'])->toBe($group->id);
    
    // DB更新確認
    $child->refresh();
    expect($child->parent_user_id)->toBe($parent->id);
    expect($child->group_id)->toBe($group->id);
    
    // 元の通知が既読に
    assertDatabaseHas('user_notifications', [
        'user_id' => $child->id,
        'notification_template_id' => $notification->id,
        'is_read' => true,
    ]);
    
    // 承認通知作成確認
    assertDatabaseHas('notification_templates', [
        'type' => 'parent_link_approved',
    ]);
    
    Queue::assertPushed(SendPushNotificationJob::class);
});

test('API response includes updated child user information', function () {
    Queue::fake();
    
    $group = Group::factory()->create(['name' => 'Test Group']);
    $parent = User::factory()->create(['is_minor' => false, 'group_id' => $group->id]);
    $child = User::factory()->create([
        'username' => 'testchild',
        'name' => 'Test Child',
        'is_minor' => true,
        'group_id' => null,
    ]);
    
    $notification = NotificationTemplate::create([
        'sender_id' => $parent->id,
        'source' => 'system',
        'type' => 'parent_link_request',
        'priority' => 'important',
        'title' => '保護者アカウントとの紐付けリクエスト',
        'message' => 'Test message',
        'data' => [
            'parent_user_id' => $parent->id,
            'group_id' => $group->id,
        ],
        'target_type' => 'users',
        'target_ids' => [$child->id],
        'publish_at' => now(),
    ]);
    
    UserNotification::create(['user_id' => $child->id, 'notification_template_id' => $notification->id]);
    
    $response = actingAs($child, 'sanctum')->postJson("/api/notifications/{$notification->id}/approve-parent-link");
    
    $data = $response->json('data');
    
    // 子アカウント情報
    expect($data['child_user'])->toMatchArray([
        'id' => $child->id,
        'username' => 'testchild',
        'parent_user_id' => $parent->id,
        'group_id' => $group->id,
    ]);
    
    // 保護者情報
    expect($data['parent_user'])->toHaveKeys(['id', 'username', 'name']);
    
    // グループ情報
    expect($data['group'])->toMatchArray([
        'id' => $group->id,
        'name' => 'Test Group',
    ]);
});

test('API creates approval notification for parent', function () {
    Queue::fake();
    
    $group = Group::factory()->create();
    $parent = User::factory()->create(['username' => 'parenttest', 'is_minor' => false, 'group_id' => $group->id]);
    $child = User::factory()->create(['username' => 'childtest', 'is_minor' => true, 'group_id' => null]);
    
    $notification = NotificationTemplate::create([
        'sender_id' => $parent->id,
        'source' => 'system',
        'type' => 'parent_link_request',
        'priority' => 'important',
        'title' => '保護者アカウントとの紐付けリクエスト',
        'message' => 'Test message',
        'data' => [
            'parent_user_id' => $parent->id,
            'group_id' => $group->id,
        ],
        'target_type' => 'users',
        'target_ids' => [$child->id],
        'publish_at' => now(),
    ]);
    
    UserNotification::create(['user_id' => $child->id, 'notification_template_id' => $notification->id]);
    
    actingAs($child, 'sanctum')->postJson("/api/notifications/{$notification->id}/approve-parent-link");
    
    $approvalNotification = NotificationTemplate::where('type', 'parent_link_approved')->first();
    
    expect($approvalNotification)->not->toBeNull();
    expect($approvalNotification->message)->toContain('childtest');
    expect($approvalNotification->data['child_user_id'])->toBe($child->id);
    expect($approvalNotification->data['child_username'])->toBe('childtest');
    
    assertDatabaseHas('user_notifications', [
        'user_id' => $parent->id,
        'notification_template_id' => $approvalNotification->id,
        'is_read' => false,
    ]);
});

test('API returns 400 when child already in group', function () {
    Queue::fake();
    
    $parentGroup = Group::factory()->create();
    $childGroup = Group::factory()->create();
    
    $parent = User::factory()->create(['is_minor' => false, 'group_id' => $parentGroup->id]);
    $child = User::factory()->create(['is_minor' => true, 'group_id' => $childGroup->id]);
    
    $notification = NotificationTemplate::create([
        'sender_id' => $parent->id,
        'source' => 'system',
        'type' => 'parent_link_request',
        'priority' => 'important',
        'title' => '保護者アカウントとの紐付けリクエスト',
        'message' => 'Test message',
        'data' => [
            'parent_user_id' => $parent->id,
            'group_id' => $parentGroup->id,
        ],
        'target_type' => 'users',
        'target_ids' => [$child->id],
        'publish_at' => now(),
    ]);
    
    UserNotification::create(['user_id' => $child->id, 'notification_template_id' => $notification->id]);
    
    $response = actingAs($child, 'sanctum')->postJson("/api/notifications/{$notification->id}/approve-parent-link");
    
    $response->assertStatus(400);
    $response->assertJson([
        'success' => false,
        'message' => '既に別のグループに所属しているため、紐付けできません。',
    ]);
    
    $response->assertJsonStructure([
        'errors' => ['group'],
    ]);
    
    // 子のグループは変更されていない
    $child->refresh();
    expect($child->group_id)->toBe($childGroup->id);
});

test('API returns 400 when notification type is invalid', function () {
    Queue::fake();
    
    $child = User::factory()->create(['is_minor' => true]);
    
    $notification = NotificationTemplate::create([
        'sender_id' => 1,
        'source' => 'system',
        'type' => 'task_assigned', // 不正な種別
        'priority' => 'normal',
        'title' => 'Test notification',
        'message' => 'Test message',
        'data' => [],
        'target_type' => 'users',
        'target_ids' => [$child->id],
        'publish_at' => now(),
    ]);
    
    $response = actingAs($child, 'sanctum')->postJson("/api/notifications/{$notification->id}/approve-parent-link");
    
    $response->assertStatus(400);
    $response->assertJson([
        'success' => false,
        'message' => '無効な通知種別です。',
    ]);
});

test('API returns 401 when unauthenticated', function () {
    $notification = NotificationTemplate::factory()->create(['type' => 'parent_link_request']);
    
    $response = postJson("/api/notifications/{$notification->id}/approve-parent-link");
    
    $response->assertStatus(401);
});
