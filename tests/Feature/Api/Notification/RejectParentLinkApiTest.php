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
use function Pest\Laravel\assertSoftDeleted;

/**
 * 親子紐付け拒否API テスト (Mobile版) - COPPA対応
 * 
 * Phase 8 - Task 8: 親子紐付け拒否機能のIntegration Test (API)
 * 
 * テスト対象:
 * - POST /api/notifications/{notification_template_id}/reject-parent-link
 * - RejectParentLinkApiAction
 * 
 * 検証項目:
 * 1. APIで子が紐付けリクエストを拒否できる
 * 2. レスポンス形式の検証 (success, message, data)
 * 3. 子アカウントがソフトデリートされる (COPPA法遵守)
 * 4. APIレスポンスにログアウト指示が含まれる
 * 5. 保護者に拒否通知が送信される
 * 6. 拒否通知にアカウント削除情報が含まれる
 * 7. Push通知ジョブがディスパッチされる
 * 8. 無効な通知種別の場合は400エラー
 * 9. 認証が必要（未ログインは401）
 */

test('child can reject parent link request via API and account is deleted', function () {
    Queue::fake();
    
    $group = Group::factory()->create();
    $parent = User::factory()->create([
        'name' => 'API Parent',
        'username' => 'apiparent',
        'is_minor' => false,
        'group_id' => $group->id,
    ]);
    
    $child = User::factory()->create([
        'username' => 'apichild',
        'email' => 'apichild@example.com',
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
    
    $response = actingAs($child, 'sanctum')->postJson("/api/notifications/{$notification->id}/reject-parent-link");
    
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'アカウントが削除されました。COPPA法により、13歳未満の方は保護者の同意と管理が必要です。',
    ]);
    
    // レスポンスデータの検証
    $data = $response->json('data');
    expect($data)->toHaveKey('deleted');
    expect($data)->toHaveKey('deleted_at');
    expect($data)->toHaveKey('reason');
    expect($data)->toHaveKey('coppa_compliance');
    
    expect($data['deleted'])->toBeTrue();
    expect($data['reason'])->toBe('parent_link_rejected');
    expect($data['coppa_compliance'])->toBeTrue();
    
    // 子アカウントがソフトデリートされている
    assertSoftDeleted('users', [
        'id' => $child->id,
        'email' => 'apichild@example.com',
    ]);
    
    // 元の通知が既読
    assertDatabaseHas('user_notifications', [
        'user_id' => $child->id,
        'notification_template_id' => $notification->id,
        'is_read' => true,
    ]);
    
    // 保護者に拒否通知作成
    assertDatabaseHas('notification_templates', [
        'type' => 'parent_link_rejected',
    ]);
    
    $rejectionNotification = NotificationTemplate::where('type', 'parent_link_rejected')->first();
    expect($rejectionNotification->message)->toContain('apichild');
    expect($rejectionNotification->message)->toContain('COPPA法');
    expect($rejectionNotification->data['child_user_id'])->toBe($child->id);
    
    Queue::assertPushed(SendPushNotificationJob::class);
});

test('API response instructs mobile app to logout and clear storage', function () {
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
    
    $response = actingAs($child, 'sanctum')->postJson("/api/notifications/{$notification->id}/reject-parent-link");
    
    $data = $response->json('data');
    
    // モバイルアプリに必要な情報
    expect($data['deleted'])->toBeTrue();
    expect($data['coppa_compliance'])->toBeTrue();
    expect($data)->toHaveKey('deleted_at');
    
    // この情報に基づいてモバイルアプリは:
    // 1. AsyncStorageのクリア
    // 2. Sanctumトークン削除
    // 3. ログイン画面へナビゲーション
    // 4. COPPA法に関するメッセージを表示
});

test('API rejection deletes child account atomically', function () {
    Queue::fake();
    
    $group = Group::factory()->create();
    $parent = User::factory()->create(['is_minor' => false, 'group_id' => $group->id]);
    $child = User::factory()->create([
        'username' => 'deletetest',
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
    
    actingAs($child, 'sanctum')->postJson("/api/notifications/{$notification->id}/reject-parent-link");
    
    // ソフトデリート確認
    assertSoftDeleted('users', ['id' => $child->id]);
    
    // 通常検索では見つからない
    $foundChild = User::where('username', 'deletetest')->first();
    expect($foundChild)->toBeNull();
    
    // withTrashedで取得可能
    $trashedChild = User::withTrashed()->where('username', 'deletetest')->first();
    expect($trashedChild)->not->toBeNull();
    expect($trashedChild->deleted_at)->not->toBeNull();
});

test('API rejection notification includes deleted_at timestamp', function () {
    Queue::fake();
    
    $group = Group::factory()->create();
    $parent = User::factory()->create(['is_minor' => false, 'group_id' => $group->id]);
    $child = User::factory()->create(['username' => 'timestamptest', 'is_minor' => true, 'group_id' => null]);
    
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
    
    actingAs($child, 'sanctum')->postJson("/api/notifications/{$notification->id}/reject-parent-link");
    
    $rejectionNotification = NotificationTemplate::where('type', 'parent_link_rejected')->first();
    $data = $rejectionNotification->data;
    
    expect($data)->toHaveKey('deleted_at');
    expect($data['deleted_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/'); // ISO 8601
    expect($data['child_username'])->toBe('timestamptest');
});

test('API returns 400 when notification type is invalid', function () {
    Queue::fake();
    
    $child = User::factory()->create(['is_minor' => true]);
    
    $notification = NotificationTemplate::create([
        'sender_id' => 1,
        'source' => 'system',
        'type' => 'task_updated', // 不正な種別
        'priority' => 'normal',
        'title' => 'Test notification',
        'message' => 'Test message',
        'data' => [],
        'target_type' => 'users',
        'target_ids' => [$child->id],
        'publish_at' => now(),
    ]);
    
    $response = actingAs($child, 'sanctum')->postJson("/api/notifications/{$notification->id}/reject-parent-link");
    
    $response->assertStatus(400);
    $response->assertJson([
        'success' => false,
        'message' => '無効な通知種別です。',
    ]);
    
    // アカウントは削除されない
    expect(User::find($child->id))->not->toBeNull();
});

test('API returns 400 when notification data is missing parent_user_id', function () {
    Queue::fake();
    
    $child = User::factory()->create(['is_minor' => true]);
    
    $notification = NotificationTemplate::create([
        'sender_id' => 1,
        'source' => 'system',
        'type' => 'parent_link_request',
        'priority' => 'important',
        'title' => '保護者アカウントとの紐付けリクエスト',
        'message' => 'Test message',
        'data' => [], // parent_user_id なし
        'target_type' => 'users',
        'target_ids' => [$child->id],
        'publish_at' => now(),
    ]);
    
    UserNotification::create(['user_id' => $child->id, 'notification_template_id' => $notification->id]);
    
    $response = actingAs($child, 'sanctum')->postJson("/api/notifications/{$notification->id}/reject-parent-link");
    
    $response->assertStatus(400);
    $response->assertJson([
        'success' => false,
        'message' => '通知データが不正です。',
    ]);
    
    // アカウントは削除されない
    expect(User::find($child->id))->not->toBeNull();
});

test('API returns 401 when unauthenticated', function () {
    $notification = NotificationTemplate::factory()->create(['type' => 'parent_link_request']);
    
    $response = postJson("/api/notifications/{$notification->id}/reject-parent-link");
    
    $response->assertStatus(401);
});

test('API dispatches push notification to parent after rejection', function () {
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
    
    actingAs($child, 'sanctum')->postJson("/api/notifications/{$notification->id}/reject-parent-link");
    
    // 保護者へのPush通知
    Queue::assertPushed(SendPushNotificationJob::class, function ($job) use ($parent) {
        return $job->getUserId() === $parent->id;
    });
});
