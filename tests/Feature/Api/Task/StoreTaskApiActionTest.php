<?php

namespace Tests\Feature\Api\Task;

use App\Models\User;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

/**
 * API: グループタスク作成制限機能のテスト
 * 
 * StoreTaskApiAction（モバイルAPI）のグループタスク制限機能をテスト
 */
class StoreTaskApiActionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * モバイルAPIでグループタスクを制限内で作成できる
     */
    public function test_api_can_create_group_task_within_limit(): void
    {
        $group = Group::factory()->create([
            'free_group_task_limit' => 3,
            'group_task_count_current_month' => 2, // 2件使用済み
            'subscription_active' => false,
        ]);
        
        $user = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => true,
        ]);
        
        $group->master_user_id = $user->id;
        $group->save();

        // 3回目の作成（成功するはず）
        $response = $this->actingAs($user)
            ->postJson('/api/tasks', [
                'title' => 'API Test Group Task',
                'description' => 'Test content',
                'span' => 1,
                'reward' => 100,
                'requires_approval' => true,
                'is_group_task' => true,
                'assigned_user_id' => $user->id,
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'タスクを作成しました。',
        ]);
        
        // カウンターが増加したことを確認
        $group->refresh();
        $this->assertEquals(3, $group->group_task_count_current_month);
    }

    /**
     * モバイルAPIでグループタスク上限到達時にエラーを返す
     */
    public function test_api_cannot_create_group_task_when_limit_reached(): void
    {
        $group = Group::factory()->create([
            'free_group_task_limit' => 3,
            'group_task_count_current_month' => 3, // 上限到達
            'subscription_active' => false,
        ]);
        
        $user = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => true,
        ]);
        
        $group->master_user_id = $user->id;
        $group->save();

        // 4回目の作成（失敗するはず）
        $response = $this->actingAs($user)
            ->postJson('/api/tasks', [
                'title' => 'API Test Group Task',
                'description' => 'Test content',
                'span' => 1,
                'reward' => 100,
                'requires_approval' => true,
                'is_group_task' => true,
                'assigned_user_id' => $user->id,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'upgrade_required' => true,
        ]);
        
        // usageデータが含まれることを確認
        $response->assertJsonStructure([
            'success',
            'message',
            'usage' => [
                'current',
                'limit',
                'remaining',
                'is_unlimited',
                'reset_at',
            ],
            'upgrade_required',
        ]);
    }

    /**
     * モバイルAPIでサブスクリプション契約者は無制限でグループタスクを作成できる
     */
    public function test_api_subscribed_group_has_unlimited_task_creation(): void
    {
        $group = Group::factory()->create([
            'free_group_task_limit' => 3,
            'group_task_count_current_month' => 10, // 無料枠を超過
            'subscription_active' => true, // サブスクリプション有効
        ]);
        
        $user = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => true,
        ]);
        
        $group->master_user_id = $user->id;
        $group->save();

        // サブスクリプション契約者は無制限で作成可能
        $response = $this->actingAs($user)
            ->postJson('/api/tasks', [
                'title' => 'API Test Group Task',
                'description' => 'Test content',
                'span' => 1,
                'reward' => 100,
                'requires_approval' => true,
                'is_group_task' => true,
                'assigned_user_id' => $user->id,
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);
    }

    /**
     * モバイルAPIでグループタスク作成権限がない場合は403エラー
     */
    public function test_api_non_editor_cannot_create_group_task(): void
    {
        // 別のユーザーをマスターとして設定
        $masterUser = User::factory()->create();
        
        $group = Group::factory()->create([
            'free_group_task_limit' => 5,
            'group_task_count_current_month' => 0,
            'subscription_active' => false,
            'master_user_id' => $masterUser->id, // 別のユーザーをマスターに
        ]);
        
        $user = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => false, // 編集権限なし
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/tasks', [
                'title' => 'API Test Group Task',
                'description' => 'Test content',
                'span' => 1,
                'reward' => 100,
                'requires_approval' => true,
                'is_group_task' => true,
                'assigned_user_id' => $user->id,
            ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'グループタスク作成権限がありません。',
        ]);
    }

    /**
     * モバイルAPIで通常タスクは制限なしで作成できる
     */
    public function test_api_can_create_normal_task_without_limit(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/tasks', [
                'title' => 'API Test Normal Task',
                'description' => 'Test content',
                'span' => 1,
                'is_group_task' => false, // 通常タスク
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);
    }
}
