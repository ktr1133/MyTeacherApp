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
use function Pest\Laravel\assertSoftDeleted;

/**
 * 親子紐付け拒否処理テスト (Web版) - COPPA対応
 * 
 * Phase 8 - Task 8: 親子紐付け拒否機能のIntegration Test
 * 
 * テスト対象:
 * - POST /notifications/{notification_template_id}/reject-parent-link
 * - RejectParentLinkAction
 * 
 * 検証項目:
 * 1. 子が紐付けリクエストを拒否できる
 * 2. 子アカウントがソフトデリートされる (COPPA法遵守)
 * 3. 子が自動的にログアウトされる
 * 4. セッションが無効化される
 * 5. 保護者に拒否通知が送信される (type: parent_link_rejected)
 * 6. 拒否通知にアカウント削除情報が含まれる
 * 7. Push通知ジョブがディスパッチされる
 * 8. 元の通知が既読になる
 * 9. ログイン画面にリダイレクトされる
 * 10. 無効な通知種別の場合はエラー
 */

test('child can reject parent link request and account is deleted', function () {
    Queue::fake();
    
    // Given: 保護者からの紐付けリクエスト
    $group = Group::factory()->create();
    $parent = User::factory()->create([
        'name' => 'Parent User',
        'username' => 'parentuser',
        'is_minor' => false,
        'group_id' => $group->id,
    ]);
    
    $child = User::factory()->create([
        'username' => 'childuser',
        'email' => 'child@example.com',
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
    
    // When: 子が拒否
    $response = actingAs($child)->post(route('notification.reject-parent-link', ['notification' => $notification->id]));
    
    // Then: ログイン画面にリダイレクト
    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status');
    
    $statusMessage = session('status');
    expect($statusMessage)->toContain('アカウントが削除されました');
    expect($statusMessage)->toContain('COPPA法');
    
    // 子アカウントがソフトデリートされている
    assertSoftDeleted('users', [
        'id' => $child->id,
        'email' => 'child@example.com',
    ]);
    
    // 元の通知が既読になっている
    assertDatabaseHas('user_notifications', [
        'user_id' => $child->id,
        'notification_template_id' => $notification->id,
        'is_read' => true,
    ]);
    
    // 保護者に拒否通知が作成されている
    assertDatabaseHas('notification_templates', [
        'sender_id' => 1, // システム管理者
        'type' => 'parent_link_rejected',
        'priority' => 'important',
        'title' => 'お子様が紐付けを拒否しました',
    ]);
    
    $rejectionNotification = NotificationTemplate::where('type', 'parent_link_rejected')->first();
    expect($rejectionNotification->message)->toContain('childuser');
    expect($rejectionNotification->message)->toContain('拒否しました');
    expect($rejectionNotification->message)->toContain('COPPA法');
    expect($rejectionNotification->message)->toContain('削除されました');
    
    $rejectionData = $rejectionNotification->data;
    expect($rejectionData['child_user_id'])->toBe($child->id);
    expect($rejectionData['child_username'])->toBe('childuser');
    expect($rejectionData)->toHaveKey('deleted_at');
    
    // 保護者へのユーザー通知レコード
    assertDatabaseHas('user_notifications', [
        'user_id' => $parent->id,
        'notification_template_id' => $rejectionNotification->id,
        'is_read' => false,
    ]);
    
    // Push通知ジョブがディスパッチされた
    Queue::assertPushed(SendPushNotificationJob::class, function ($job) use ($parent) {
        return $job->getUserId() === $parent->id;
    });
});

test('rejection logs out child automatically', function () {
    Queue::fake();
    
    $group = Group::factory()->create();
    $parent = User::factory()->create(['is_minor' => false, 'group_id' => $group->id]);
    $child = User::factory()->create(['is_minor' => true, 'group_id' => null]);
    
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
    
    $response = actingAs($child)->post(route('notification.reject-parent-link', ['notification' => $notification->id]));
    
    // ログアウトしている（未認証状態）
    $response->assertRedirect(route('login'));
    expect(auth()->check())->toBeFalse();
});

test('rejection notification includes account deletion timestamp', function () {
    Queue::fake();
    
    $group = Group::factory()->create();
    $parent = User::factory()->create(['is_minor' => false, 'group_id' => $group->id]);
    $child = User::factory()->create(['username' => 'deletedchild', 'is_minor' => true, 'group_id' => null]);
    
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
    
    actingAs($child)->post(route('notification.reject-parent-link', ['notification' => $notification->id]));
    
    $rejectionNotification = NotificationTemplate::where('type', 'parent_link_rejected')->first();
    $data = $rejectionNotification->data;
    
    expect($data)->toHaveKey('deleted_at');
    expect($data['deleted_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+Z$/'); // ISO 8601形式
    expect($data['child_username'])->toBe('deletedchild');
});

test('rejection prevents re-authentication with deleted account', function () {
    Queue::fake();
    
    $group = Group::factory()->create();
    $parent = User::factory()->create(['is_minor' => false, 'group_id' => $group->id]);
    $child = User::factory()->create([
        'email' => 'deleted@example.com',
        'password' => bcrypt('password'),
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
    
    actingAs($child)->post(route('notification.reject-parent-link', ['notification' => $notification->id]));
    
    // ソフトデリート確認
    assertSoftDeleted('users', ['id' => $child->id]);
    
    // 削除されたアカウントは検索結果から除外される
    $foundChild = User::where('email', 'deleted@example.com')->first();
    expect($foundChild)->toBeNull();
    
    // withTrashedで取得可能
    $trashedChild = User::withTrashed()->where('email', 'deleted@example.com')->first();
    expect($trashedChild)->not->toBeNull();
    expect($trashedChild->deleted_at)->not->toBeNull();
});

test('rejection handles notification with missing parent_user_id gracefully', function () {
    Queue::fake();
    
    $child = User::factory()->create(['is_minor' => true]);
    
    $notification = NotificationTemplate::create([
        'sender_id' => 1,
        'source' => 'system',
        'type' => 'parent_link_request',
        'priority' => 'important',
        'title' => '保護者アカウントとの紐付けリクエスト',
        'message' => 'Test message',
        'data' => [], // parent_user_id がない
        'target_type' => 'users',
        'target_ids' => [$child->id],
        'publish_at' => now(),
    ]);
    
    UserNotification::create(['user_id' => $child->id, 'notification_template_id' => $notification->id]);
    
    $response = actingAs($child)->post(route('notification.reject-parent-link', ['notification' => $notification->id]));
    
    $response->assertRedirect();
    $response->assertSessionHasErrors('error');
    
    // アカウントは削除されない
    expect(User::find($child->id))->not->toBeNull();
});

test('cannot reject notification with invalid type', function () {
    Queue::fake();
    
    $child = User::factory()->create(['is_minor' => true]);
    
    $notification = NotificationTemplate::create([
        'sender_id' => 1,
        'source' => 'system',
        'type' => 'task_reminder', // 不正な種別
        'priority' => 'normal',
        'title' => 'Test notification',
        'message' => 'Test message',
        'data' => [],
        'target_type' => 'users',
        'target_ids' => [$child->id],
        'publish_at' => now(),
    ]);
    
    $response = actingAs($child)->post(route('notification.reject-parent-link', ['notification' => $notification->id]));
    
    $response->assertRedirect();
    $response->assertSessionHasErrors('error');
    
    // アカウントは削除されない
    expect(User::find($child->id))->not->toBeNull();
});

test('unauthenticated user cannot reject parent link', function () {
    $notification = NotificationTemplate::factory()->create(['type' => 'parent_link_request']);
    
    $response = post(route('notification.reject-parent-link', ['notification' => $notification->id]));
    
    $response->assertRedirect(route('login'));
});
