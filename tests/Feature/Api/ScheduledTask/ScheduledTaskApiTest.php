<?php

namespace Tests\Feature\Api\ScheduledTask;

use App\Models\User;
use App\Models\Group;
use App\Models\ScheduledGroupTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ScheduledTask API 統合テスト
 * 
 * Phase 1.E-1.5.3: スケジュールタスクAPI（8 Actions）
 * 
 * テスト対象:
 * 1. IndexScheduledTaskApiAction - 一覧取得
 * 2. CreateScheduledTaskApiAction - 作成情報取得
 * 3. StoreScheduledTaskApiAction - 新規作成
 * 4. EditScheduledTaskApiAction - 編集情報取得
 * 5. UpdateScheduledTaskApiAction - 更新
 * 6. DeleteScheduledTaskApiAction - 削除
 * 7. PauseScheduledTaskApiAction - 一時停止
 * 8. ResumeScheduledTaskApiAction - 再開
 */
class ScheduledTaskApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        // グループとユーザー作成
        $this->group = Group::factory()->create();
        $this->user = User::factory()->create([
            'cognito_sub' => 'cognito-sub-scheduled-test',
            'email' => 'scheduleduser@test.com',
            'username' => 'scheduleduser',
            'auth_provider' => 'cognito',
            'group_id' => $this->group->id,
            'group_edit_flg' => true,
        ]);
    }

    /**
     * @test
     * スケジュールタスク一覧を取得できること
     */
    public function test_can_get_scheduled_task_list(): void
    {
        // Arrange
        ScheduledGroupTask::factory()->count(3)->create([
            'group_id' => $this->group->id,
            'created_by' => $this->user->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/scheduled-tasks?group_id=' . $this->group->id);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'scheduled_tasks' => [
                        '*' => [
                            'id',
                            'group_id',
                            'title',
                            'description',
                            'is_active',
                        ],
                    ],
                ],
            ]);
    }

    /**
     * @test
     * group_idパラメータがない場合エラーを返すこと
     */
    public function test_returns_error_without_group_id(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/scheduled-tasks');

        // Assert
        $response->assertStatus(400);
    }

    /**
     * @test
     * 権限がない場合403エラーを返すこと
     */
    public function test_returns_403_without_permission(): void
    {
        // Arrange
        $otherGroup = Group::factory()->create();
        /** @var User $otherUser */
        $otherUser = User::factory()->create([
            'group_id' => $otherGroup->id,
            'group_edit_flg' => false,
        ]);

        // Act
        $response = $this->actingAs($otherUser)
            ->getJson('/api/scheduled-tasks?group_id=' . $this->group->id);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * @test
     * スケジュールタスク作成情報を取得できること
     */
    public function test_can_get_create_info(): void
    {
        // Arrange
        User::factory()->count(2)->create([
            'group_id' => $this->group->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/scheduled-tasks/create?group_id=' . $this->group->id);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'group_id',
                    'group_members',
                ],
            ]);
    }

    /**
     * @test
     * スケジュールタスクを作成できること
     */
    public function test_can_create_scheduled_task(): void
    {
        // Arrange
        $data = [
            'group_id' => $this->group->id,
            'title' => 'Daily Task',
            'description' => 'Test description',
            'requires_image' => false,
            'requires_approval' => true,
            'reward' => 100,
            'auto_assign' => true,
            'schedules' => [
                [
                    'type' => 'daily',
                    'time' => '09:00',
                ],
            ],
            'due_duration_days' => 1,
            'due_duration_hours' => 0,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'skip_holidays' => false,
            'move_to_next_business_day' => false,
            'delete_incomplete_previous' => false,
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/scheduled-tasks', $data);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'scheduled_task' => [
                        'id',
                        'title',
                        'group_id',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('scheduled_group_tasks', [
            'title' => 'Daily Task',
            'group_id' => $this->group->id,
        ]);
    }

    /**
     * @test
     * スケジュールタスク編集情報を取得できること
     */
    public function test_can_get_edit_info(): void
    {
        // Arrange
        $scheduledTask = ScheduledGroupTask::factory()->create([
            'group_id' => $this->group->id,
            'created_by' => $this->user->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/scheduled-tasks/' . $scheduledTask->id . '/edit');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'scheduled_task',
                    'group_id',
                    'group_members',
                ],
            ]);
    }

    /**
     * @test
     * スケジュールタスクを更新できること
     */
    public function test_can_update_scheduled_task(): void
    {
        // Arrange
        $scheduledTask = ScheduledGroupTask::factory()->create([
            'group_id' => $this->group->id,
            'created_by' => $this->user->id,
            'title' => 'Old Title',
        ]);

        $data = [
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'requires_image' => false,
            'requires_approval' => true,
            'reward' => 200,
            'auto_assign' => true,
            'schedules' => [
                [
                    'type' => 'daily',
                    'time' => '10:00',
                ],
            ],
            'due_duration_days' => 1,
            'due_duration_hours' => 0,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'skip_holidays' => false,
            'move_to_next_business_day' => false,
            'delete_incomplete_previous' => false,
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->putJson('/api/scheduled-tasks/' . $scheduledTask->id, $data);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'スケジュールタスクを更新しました。',
            ]);

        $this->assertDatabaseHas('scheduled_group_tasks', [
            'id' => $scheduledTask->id,
            'title' => 'Updated Title',
        ]);
    }

    /**
     * @test
     * スケジュールタスクを削除できること
     */
    public function test_can_delete_scheduled_task(): void
    {
        // Arrange
        $scheduledTask = ScheduledGroupTask::factory()->create([
            'group_id' => $this->group->id,
            'created_by' => $this->user->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/scheduled-tasks/' . $scheduledTask->id);

        // Assert
        $response->assertStatus(200);

        // ソフトデリート実装のためassertSoftDeletedを使用
        $this->assertSoftDeleted('scheduled_group_tasks', [
            'id' => $scheduledTask->id,
        ]);
    }

    /**
     * @test
     * スケジュールタスクを一時停止できること
     */
    public function test_can_pause_scheduled_task(): void
    {
        // Arrange
        $scheduledTask = ScheduledGroupTask::factory()->create([
            'group_id' => $this->group->id,
            'created_by' => $this->user->id,
            'is_active' => true,
            'paused_at' => null,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/scheduled-tasks/' . $scheduledTask->id . '/pause');

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('scheduled_group_tasks', [
            'id' => $scheduledTask->id,
            'is_active' => false,
        ]);
    }

    /**
     * @test
     * スケジュールタスクを再開できること
     */
    public function test_can_resume_scheduled_task(): void
    {
        // Arrange
        $scheduledTask = ScheduledGroupTask::factory()->paused()->create([
            'group_id' => $this->group->id,
            'created_by' => $this->user->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/scheduled-tasks/' . $scheduledTask->id . '/resume');

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('scheduled_group_tasks', [
            'id' => $scheduledTask->id,
            'is_active' => true,
            'paused_at' => null,
        ]);
    }

    /**
     * @test
     * バリデーションエラーが正しく返されること
     */
    public function test_validation_error_on_invalid_data(): void
    {
        // Arrange - 必須フィールドを欠落させてエラーを検出
        // 注: requires_approvalはprepareForValidation()でboolean変換されるためrequiredエラーは出ない
        $data = [
            'group_id' => $this->group->id,
            // title欠落
            // reward欠落
            // schedules欠落
            // start_date欠落
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/scheduled-tasks', $data);

        // Assert - 必須フィールドのバリデーションエラー（requires_approval以外）
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'reward', 'schedules', 'start_date']);
    }
}
