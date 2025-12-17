<?php

use App\Models\User;
use App\Models\Group;
use App\Models\NotificationTemplate;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SendPushNotificationJob;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\assertDatabaseHas;

/**
 * 親子紐付け承認処理テスト (Web版)
 * 
 * Phase 8 - Task 7: 親子紐付け承認機能のIntegration Test
 * 
 * テスト対象:
 * - POST /notifications/{notification_template_id}/approve-parent-link
 * - ApproveParentLinkAction
 * 
 * 検証項目:
 * 1. 子が紐付けリクエストを承認できる
 * 2. child.parent_user_id が設定される
 * 3. child.group_id が設定される
 * 4. 元の通知が既読になる
 * 5. 保護者に承認通知が送信される (type: parent_link_approved)
 * 6. Push通知ジョブがディスパッチされる
 * 7. 子が既にグループ所属の場合はエラー
 * 8. 無効な通知種別の場合はエラー
 * 9. 認証が必要
 */

test('child can approve parent link request', function () {
    Queue::fake();
    
    // Given: 保護者からの紐付けリクエスト通知
    $group = Group::factory()->create(['name' => 'Approval Test Group']);
    
    $parent = User::factory()->create([
        'name' => 'Parent User',
        'username' => 'parentuser',
        'is_minor' => false,
        'group_id' => $group->id,
    ]);
    
    $child = User::factory()->create([
        'username' => 'childuser',
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
        'message' => 'Test message',
        'data' => [
            'parent_user_id' => $parent->id,
            'parent_name' => $parent->name,
            'group_id' => $group->id,
            'group_name' => $group->name,
        ],
        'target_type' => 'users',
        'target_ids' => [$child->id],
        'publish_at' => now(),
        'expire_at' => null,
    ]);
    
    $userNotification = UserNotification::create([
        'user_id' => $child->id,
        'notification_template_id' => $notification->id,
        'is_read' => false,
    ]);
    
    // When: 子が承認
    $response = actingAs($child)->post(route('notification.approve-parent-link', ['notification' => $notification->id]));
    
    // Then: 成功してリダイレクト
    $response->assertRedirect(route('notifications.index'));
    $response->assertSessionHas('status');
    
    // 子アカウントが更新されている
    $child->refresh();
    expect($child->parent_user_id)->toBe($parent->id);
    expect($child->group_id)->toBe($group->id);
    
    // 元の通知が既読に
    assertDatabaseHas('user_notifications', [
        'user_id' => $child->id,
        'notification_template_id' => $notification->id,
        'is_read' => true,
    ]);
    
    // 保護者に承認通知が作成されている
    assertDatabaseHas('notification_templates', [
        'sender_id' => 1, // システム管理者
        'type' => 'parent_link_approved',
        'priority' => 'normal',
        'title' => 'お子様が紐付けを承認しました',
    ]);
    
    $approvalNotification = NotificationTemplate::where('type', 'parent_link_approved')->first();
    expect($approvalNotification->message)->toContain('childuser');
    expect($approvalNotification->message)->toContain('承認しました');
    
    $approvalData = $approvalNotification->data;
    expect($approvalData['child_user_id'])->toBe($child->id);
    expect($approvalData['child_username'])->toBe('childuser');
    expect($approvalData['group_id'])->toBe($group->id);
    
    // 保護者へのユーザー通知レコード
    assertDatabaseHas('user_notifications', [
        'user_id' => $parent->id,
        'notification_template_id' => $approvalNotification->id,
        'is_read' => false,
    ]);
    
    // Push通知ジョブがディスパッチされた
    Queue::assertPushed(SendPushNotificationJob::class, function ($job) use ($parent) {
        return $job->getUserId() === $parent->id;
    });
});

test('approval updates child parent_user_id and group_id atomically', function () {
    Queue::fake();
    
    $group = Group::factory()->create();
    $parent = User::factory()->create(['is_minor' => false, 'group_id' => $group->id]);
    $child = User::factory()->create(['is_minor' => true, 'parent_user_id' => null, 'group_id' => null]);
    
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
    
    UserNotification::create([
        'user_id' => $child->id,
        'notification_template_id' => $notification->id,
        'is_read' => false,
    ]);
    
    actingAs($child)->post(route('notification.approve-parent-link', ['notification' => $notification->id]));
    
    $child->refresh();
    
    // 両方とも同時に設定されている
    expect($child->parent_user_id)->not->toBeNull();
    expect($child->group_id)->not->toBeNull();
    expect($child->parent_user_id)->toBe($parent->id);
    expect($child->group_id)->toBe($group->id);
});

test('approval notification includes group information', function () {
    Queue::fake();
    
    $group = Group::factory()->create(['name' => 'Family Group']);
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
    
    actingAs($child)->post(route('notification.approve-parent-link', ['notification' => $notification->id]));
    
    $approvalNotification = NotificationTemplate::where('type', 'parent_link_approved')->first();
    
    expect($approvalNotification->message)->toContain('Family Group');
    expect($approvalNotification->message)->toContain('タスク管理機能');
});

test('cannot approve when child already in group', function () {
    Queue::fake();
    
    $parentGroup = Group::factory()->create();
    $childGroup = Group::factory()->create();
    
    $parent = User::factory()->create(['is_minor' => false, 'group_id' => $parentGroup->id]);
    $child = User::factory()->create(['is_minor' => true, 'group_id' => $childGroup->id]); // 既にグループ所属
    
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
    
    $response = actingAs($child)->post(route('notification.approve-parent-link', ['notification' => $notification->id]));
    
    $response->assertRedirect();
    $response->assertSessionHasErrors('error');
    
    // 子のグループは変更されていない
    $child->refresh();
    expect($child->group_id)->toBe($childGroup->id);
    
    // 承認通知は作成されない
    expect(NotificationTemplate::where('type', 'parent_link_approved')->count())->toBe(0);
});

test('cannot approve notification with invalid type', function () {
    Queue::fake();
    
    $child = User::factory()->create(['is_minor' => true]);
    
    // 別種別の通知
    $notification = NotificationTemplate::create([
        'sender_id' => 1,
        'source' => 'system',
        'type' => 'task_completed', // 紐付けリクエストではない
        'priority' => 'normal',
        'title' => 'Test notification',
        'message' => 'Test message',
        'data' => [],
        'target_type' => 'users',
        'target_ids' => [$child->id],
        'publish_at' => now(),
    ]);
    
    $response = actingAs($child)->post(route('notification.approve-parent-link', ['notification' => $notification->id]));
    
    $response->assertRedirect();
    $response->assertSessionHasErrors('error');
    
    // 承認通知は作成されない
    expect(NotificationTemplate::where('type', 'parent_link_approved')->count())->toBe(0);
});

test('unauthenticated user cannot approve parent link', function () {
    $notification = NotificationTemplate::factory()->create(['type' => 'parent_link_request']);
    
    $response = post(route('notification.approve-parent-link', ['notification' => $notification->id]));
    
    $response->assertRedirect(route('login'));
});
