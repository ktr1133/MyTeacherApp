<?php

namespace Tests\Feature\Group;

use App\Models\User;
use App\Models\Group;
use App\Services\Group\GroupTaskLimitServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

/**
 * グループタスク作成制限機能のテスト
 */
class GroupTaskLimitTest extends TestCase
{
    use RefreshDatabase;

    protected GroupTaskLimitServiceInterface $groupTaskLimitService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->groupTaskLimitService = app(GroupTaskLimitServiceInterface::class);
    }

    /**
     * 無料プランのグループは制限内でグループタスクを作成できる
     */
    public function test_free_group_can_create_tasks_within_limit(): void
    {
        // グループとユーザーを作成
        $group = Group::factory()->create([
            'free_group_task_limit' => 3,
            'group_task_count_current_month' => 0,
            'subscription_active' => false,
        ]);
        
        $user = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => true,
        ]);
        
        $group->master_user_id = $user->id;
        $group->save();

        // 制限内であることを確認
        $this->assertTrue($this->groupTaskLimitService->canCreateGroupTask($group));
        
        // 使用状況を確認
        $usage = $this->groupTaskLimitService->getGroupTaskUsage($group);
        $this->assertEquals(0, $usage['current']);
        $this->assertEquals(3, $usage['limit']);
        $this->assertEquals(3, $usage['remaining']);
        $this->assertFalse($usage['has_subscription']);
    }

    /**
     * 無料プランのグループは制限到達後にグループタスクを作成できない
     */
    public function test_free_group_cannot_create_tasks_when_limit_reached(): void
    {
        $group = Group::factory()->create([
            'free_group_task_limit' => 3,
            'group_task_count_current_month' => 3, // 上限到達
            'subscription_active' => false,
        ]);

        // 制限到達
        $this->assertFalse($this->groupTaskLimitService->canCreateGroupTask($group));
        
        $usage = $this->groupTaskLimitService->getGroupTaskUsage($group);
        $this->assertEquals(3, $usage['current']);
        $this->assertEquals(0, $usage['remaining']);
    }

    /**
     * サブスクリプション有効なグループは無制限にグループタスクを作成できる
     */
    public function test_subscribed_group_has_unlimited_task_creation(): void
    {
        $group = Group::factory()->create([
            'free_group_task_limit' => 3,
            'group_task_count_current_month' => 100, // 無料枠を大幅に超過
            'subscription_active' => true,
            'subscription_plan' => 'family',
        ]);

        // サブスクリプション有効なので無制限
        $this->assertTrue($this->groupTaskLimitService->canCreateGroupTask($group));
        
        $usage = $this->groupTaskLimitService->getGroupTaskUsage($group);
        $this->assertTrue($usage['has_subscription']);
    }

    /**
     * グループタスク作成カウンターが正しく増加する
     */
    public function test_group_task_count_increments_correctly(): void
    {
        $group = Group::factory()->create([
            'free_group_task_limit' => 3,
            'group_task_count_current_month' => 0,
            'subscription_active' => false,
        ]);

        // 1回目
        $this->groupTaskLimitService->incrementGroupTaskCount($group);
        $group->refresh();
        $this->assertEquals(1, $group->group_task_count_current_month);

        // 2回目
        $this->groupTaskLimitService->incrementGroupTaskCount($group);
        $group->refresh();
        $this->assertEquals(2, $group->group_task_count_current_month);

        // 3回目
        $this->groupTaskLimitService->incrementGroupTaskCount($group);
        $group->refresh();
        $this->assertEquals(3, $group->group_task_count_current_month);
    }

    /**
     * 月次リセットが正しく動作する
     */
    public function test_monthly_count_resets_correctly(): void
    {
        $group = Group::factory()->create([
            'free_group_task_limit' => 3,
            'group_task_count_current_month' => 3, // 上限到達
            'group_task_count_reset_at' => Carbon::now()->addMonth(), // 未来の日付（リセット不要）
            'subscription_active' => false,
        ]);

        // リセット前は作成不可（上限到達）
        $this->assertFalse($this->groupTaskLimitService->canCreateGroupTask($group));
        $this->assertEquals(3, $group->group_task_count_current_month);

        // 月次リセット実行
        $this->groupTaskLimitService->resetMonthlyCount($group);
        $group->refresh();

        // リセット後
        $this->assertEquals(0, $group->group_task_count_current_month);
        $this->assertNotNull($group->group_task_count_reset_at);
        $this->assertTrue($group->group_task_count_reset_at->isFuture());
        
        // 作成可能になる
        $this->assertTrue($this->groupTaskLimitService->canCreateGroupTask($group));
    }

    /**
     * リセット日時が過ぎている場合、自動的にリセットされる
     */
    public function test_auto_reset_when_reset_date_passed(): void
    {
        $group = Group::factory()->create([
            'free_group_task_limit' => 3,
            'group_task_count_current_month' => 3,
            'group_task_count_reset_at' => Carbon::now()->subDay(), // 過去
            'subscription_active' => false,
        ]);

        // canCreateGroupTask呼び出しで自動リセット
        $canCreate = $this->groupTaskLimitService->canCreateGroupTask($group);
        
        $group->refresh();
        $this->assertTrue($canCreate);
        $this->assertEquals(0, $group->group_task_count_current_month);
    }

    /**
     * グループタスク作成時に制限チェックが機能する
     */
    public function test_task_creation_respects_limit(): void
    {
        $group = Group::factory()->create([
            'free_group_task_limit' => 3,
            'group_task_count_current_month' => 2,
            'subscription_active' => false,
        ]);
        
        $user = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => true,
        ]);
        
        $group->master_user_id = $user->id;
        $group->save();

        // 3回目の作成（成功するはず - グループタスクはJSON応答）
        $response = $this->actingAs($user)->post(route('tasks.store'), [
            'title' => 'Test Group Task',
            'description' => 'Test content',
            'span' => 1, // 必須フィールド
            'reward' => 100,
            'is_group_task' => true,
            'assigned_user_id' => $user->id,
        ]);

        $response->assertStatus(200); // JSON応答（成功）
        $response->assertJson([
            'message' => 'グループタスクが登録されました。',
        ]);
        
        $group->refresh();
        $this->assertEquals(3, $group->group_task_count_current_month);

        // 4回目の作成（失敗するはず - JSON応答を要求）
        $response = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('tasks.store'), [
                'title' => 'Another Group Task',
                'description' => 'Test content',
                'span' => 1,
                'reward' => 100,
                'is_group_task' => true,
                'assigned_user_id' => $user->id,
            ]);

        $response->assertStatus(422); // エラーステータス
        $response->assertJson([
            'upgrade_required' => true,
        ]);
    }

    /**
     * システム管理者がグループ設定を更新できる
     */
    public function test_admin_can_update_group_limits(): void
    {
        // 管理者ユーザー作成
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        // グループとメンバー作成
        $group = Group::factory()->create([
            'free_group_task_limit' => 3,
            'free_trial_days' => 14,
        ]);
        
        $member = User::factory()->create([
            'group_id' => $group->id,
        ]);
        
        $group->master_user_id = $member->id;
        $group->save();

        // 管理者がグループ設定を更新
        $response = $this->actingAs($admin)->put(route('admin.users.update', $member), [
            'username' => $member->username,
            'is_admin' => false,
            'group_edit_flg' => false,
            'free_group_task_limit' => 10,
            'free_trial_days' => 30,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        
        $group->refresh();
        $this->assertEquals(10, $group->free_group_task_limit);
        $this->assertEquals(30, $group->free_trial_days);
    }

    /**
     * 一般ユーザーは管理者画面でグループ設定を更新できない
     */
    public function test_non_admin_cannot_update_group_limits(): void
    {
        $group = Group::factory()->create([
            'free_group_task_limit' => 3,
        ]);
        
        $user = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => true,
            'is_admin' => false, // 非管理者
        ]);
        
        $group->master_user_id = $user->id;
        $group->save();

        // 非管理者が更新を試みる
        $response = $this->actingAs($user)->put(route('admin.users.update', $user), [
            'username' => $user->username,
            'free_group_task_limit' => 100,
        ]);

        $response->assertForbidden(); // 403エラー
    }

    /**
     * 使用状況取得が正しく動作する
     */
    public function test_get_group_task_usage_returns_correct_data(): void
    {
        $resetAt = Carbon::now()->addMonth()->startOfMonth();
        
        $group = Group::factory()->create([
            'free_group_task_limit' => 5,
            'group_task_count_current_month' => 2,
            'group_task_count_reset_at' => $resetAt,
            'subscription_active' => false,
        ]);

        $usage = $this->groupTaskLimitService->getGroupTaskUsage($group);

        $this->assertEquals(2, $usage['current']);
        $this->assertEquals(5, $usage['limit']);
        $this->assertEquals(3, $usage['remaining']);
        $this->assertFalse($usage['has_subscription']);
        $this->assertEquals($resetAt->toDateTimeString(), $usage['reset_at']);
    }
}
