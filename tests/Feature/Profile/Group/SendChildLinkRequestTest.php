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
 * 紐付けリクエスト送信テスト (Web版)
 * 
 * Phase 8 - Task 6: 紐付けリクエスト送信機能のIntegration Test
 * 
 * テスト対象:
 * - POST /profile/group/send-link-request
 * - SendChildLinkRequestAction
 * 
 * 検証項目:
 * 1. 保護者から子へ紐付けリクエストを送信できる
 * 2. 通知テンプレートが正しく作成される (type: parent_link_request)
 * 3. 通知データに必要な情報が含まれる (parent_user_id, group_id等)
 * 4. ユーザー通知レコードが作成される
 * 5. Push通知ジョブがディスパッチされる
 * 6. 子が既にグループ所属の場合はエラー
 * 7. 保護者がグループ未所属の場合はエラー
 * 8. 認証が必要
 */

test('parent can send link request to unlinked child', function () {
    Queue::fake();
    
    // Given: グループに所属する保護者と未紐付けの子
    $group = Group::factory()->create(['name' => 'Test Family']);
    
    $parent = User::factory()->create([
        'name' => 'Parent User',
        'username' => 'parentuser',
        'is_minor' => false,
        'group_id' => $group->id,
    ]);
    
    $child = User::factory()->create([
        'username' => 'childuser',
        'parent_email' => $parent->email,
        'is_minor' => true,
        'parent_user_id' => null,
        'group_id' => null,
    ]);
    
    // When: 保護者が紐付けリクエストを送信
    $response = actingAs($parent)->post(route('profile.group.send-link-request'), [
        'child_user_id' => $child->id,
    ]);
    
    // Then: 成功してリダイレクト
    $response->assertRedirect();
    
    // 通知テンプレート作成確認
    assertDatabaseHas('notification_templates', [
        'sender_id' => $parent->id,
        'source' => 'system',
        'type' => 'parent_link_request',
        'priority' => 'important',
        'title' => '保護者アカウントとの紐付けリクエスト',
    ]);
    
    $notification = NotificationTemplate::where('type', 'parent_link_request')
        ->where('sender_id', $parent->id)
        ->first();
    
    expect($notification)->not->toBeNull();
    expect($notification->expire_at)->toBeNull(); // 期限なし
    
    // 通知データの検証
    $data = $notification->data;
    expect($data)->toHaveKey('parent_user_id');
    expect($data)->toHaveKey('parent_name');
    expect($data)->toHaveKey('group_id');
    expect($data)->toHaveKey('group_name');
    
    expect($data['parent_user_id'])->toBe($parent->id);
    expect($data['group_id'])->toBe($group->id);
    expect($data['group_name'])->toBe('Test Family');
    
    // ユーザー通知レコード作成確認
    assertDatabaseHas('user_notifications', [
        'user_id' => $child->id,
        'notification_template_id' => $notification->id,
        'is_read' => false,
    ]);
    
    // Push通知ジョブがディスパッチされたか確認
    Queue::assertPushed(SendPushNotificationJob::class, function ($job) use ($child) {
        return $job->getUserId() === $child->id;
    });
});

test('notification message includes parent and group information', function () {
    Queue::fake();
    
    $group = Group::factory()->create(['name' => 'Smith Family']);
    
    $parent = User::factory()->create([
        'name' => 'John Smith',
        'username' => 'johnsmith',
        'is_minor' => false,
        'group_id' => $group->id,
    ]);
    
    $child = User::factory()->create([
        'is_minor' => true,
        'group_id' => null,
    ]);
    
    actingAs($parent)->post(route('profile.group.send-link-request'), [
        'child_user_id' => $child->id,
    ]);
    
    $notification = NotificationTemplate::where('type', 'parent_link_request')->first();
    
    // メッセージに保護者名とグループ名が含まれている
    expect($notification->message)->toContain('John Smith');
    expect($notification->message)->toContain('Smith Family');
    expect($notification->message)->toContain('タスクを管理できるようになります');
});

test('notification target_ids includes child user id', function () {
    Queue::fake();
    
    $group = Group::factory()->create();
    $parent = User::factory()->create(['is_minor' => false, 'group_id' => $group->id]);
    $child = User::factory()->create(['is_minor' => true, 'group_id' => null]);
    
    actingAs($parent)->post(route('profile.group.send-link-request'), [
        'child_user_id' => $child->id,
    ]);
    
    $notification = NotificationTemplate::where('type', 'parent_link_request')->first();
    
    $targetIds = $notification->target_ids;
    expect($targetIds)->toContain($child->id);
});

test('cannot send link request when child already in group', function () {
    Queue::fake();
    
    $parentGroup = Group::factory()->create();
    $childGroup = Group::factory()->create();
    
    $parent = User::factory()->create([
        'is_minor' => false,
        'group_id' => $parentGroup->id,
    ]);
    
    $child = User::factory()->create([
        'is_minor' => true,
        'group_id' => $childGroup->id, // 既にグループ所属
    ]);
    
    $response = actingAs($parent)->post(route('profile.group.send-link-request'), [
        'child_user_id' => $child->id,
    ]);
    
    // エラーでリダイレクト
    $response->assertRedirect();
    $response->assertSessionHasErrors('child_user_id');
    
    // 通知は作成されない
    expect(NotificationTemplate::where('type', 'parent_link_request')->count())->toBe(0);
    
    // Push通知ジョブもディスパッチされない
    Queue::assertNothingPushed();
});

test('cannot send link request when parent not in group', function () {
    Queue::fake();
    
    $parent = User::factory()->create([
        'is_minor' => false,
        'group_id' => null, // グループ未所属
    ]);
    
    $child = User::factory()->create([
        'is_minor' => true,
        'group_id' => null,
    ]);
    
    $response = actingAs($parent)->post(route('profile.group.send-link-request'), [
        'child_user_id' => $child->id,
    ]);
    
    // エラーでリダイレクト
    $response->assertRedirect();
    $response->assertSessionHasErrors('child_user_id');
    
    // エラーメッセージの確認
    $errors = session('errors');
    $errorMessage = $errors->get('child_user_id')[0] ?? '';
    expect($errorMessage)->toContain('グループに所属していないため');
    expect($errorMessage)->toContain('先にグループを作成してください');
    
    // 通知は作成されない
    expect(NotificationTemplate::where('type', 'parent_link_request')->count())->toBe(0);
});

test('unauthenticated user cannot send link request', function () {
    $child = User::factory()->create(['is_minor' => true]);
    
    $response = post(route('profile.group.send-link-request'), [
        'child_user_id' => $child->id,
    ]);
    
    $response->assertRedirect(route('login'));
});
