<?php

namespace Tests\Feature\Batch;

use Tests\TestCase;
use App\Models\User;
use App\Models\ScheduledTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ScheduledTaskManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        // 管理者ユーザー（canEditGroup = true）
        $this->admin = User::factory()->create([
            'group_id' => 1,
            'role' => 'admin',
        ]);

        // 一般メンバー
        $this->member = User::factory()->create([
            'group_id' => 1,
            'role' => 'member',
        ]);
    }

    /** @test */
    public function 管理者はスケジュールタスク一覧を表示できる(): void
    {
        ScheduledTask::factory()->count(3)->create(['group_id' => 1]);

        $response = $this->actingAs($this->admin)
            ->get(route('batch.scheduled-tasks.index', ['group_id' => 1]));

        $response->assertStatus(200);
        $response->assertViewIs('batch.index');
        $response->assertViewHas('scheduledTasks');
    }

    /** @test */
    public function 権限がないユーザーは一覧を表示できない(): void
    {
        $otherUser = User::factory()->create([
            'group_id' => 2,
            'role' => 'member',
        ]);

        $response = $this->actingAs($otherUser)
            ->get(route('batch.scheduled-tasks.index', ['group_id' => 1]));

        $response->assertStatus(403);
    }

    /** @test */
    public function 管理者はスケジュールタスク作成画面を表示できる(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('batch.scheduled-tasks.create', ['group_id' => 1]));

        $response->assertStatus(200);
        $response->assertViewIs('batch.create');
        $response->assertViewHas('groupMembers');
    }

    /** @test */
    public function 管理者はスケジュールタスクを作成できる(): void
    {
        $data = [
            'group_id' => 1,
            'title' => 'テストタスク',
            'description' => 'テスト説明',
            'schedules' => [
                [
                    'type' => 'daily',
                    'time' => '09:00',
                ]
            ],
            'reward' => 100,
            'requires_image' => false,
            'auto_assign' => false,
            'assigned_user_id' => $this->member->id,
            'start_date' => Carbon::now()->format('Y-m-d'),
            'tags' => ['test', 'sample'],
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('batch.scheduled-tasks.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'scheduled-task-created');

        $this->assertDatabaseHas('scheduled_tasks', [
            'group_id' => 1,
            'title' => 'テストタスク',
            'reward' => 100,
        ]);
    }

    /** @test */
    public function タイトルなしでは作成できない(): void
    {
        $data = [
            'group_id' => 1,
            'schedules' => [
                ['type' => 'daily', 'time' => '09:00']
            ],
            'reward' => 100,
            'start_date' => Carbon::now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('batch.scheduled-tasks.store'), $data);

        $response->assertSessionHasErrors('title');
    }

    /** @test */
    public function スケジュールなしでは作成できない(): void
    {
        $data = [
            'group_id' => 1,
            'title' => 'テストタスク',
            'reward' => 100,
            'start_date' => Carbon::now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('batch.scheduled-tasks.store'), $data);

        $response->assertSessionHasErrors('schedules');
    }

    /** @test */
    public function 管理者はスケジュールタスク編集画面を表示できる(): void
    {
        $scheduledTask = ScheduledTask::factory()->create(['group_id' => 1]);

        $response = $this->actingAs($this->admin)
            ->get(route('batch.scheduled-tasks.edit', $scheduledTask->id));

        $response->assertStatus(200);
        $response->assertViewIs('batch.edit');
        $response->assertViewHas('scheduledTask');
    }

    /** @test */
    public function 管理者はスケジュールタスクを更新できる(): void
    {
        $scheduledTask = ScheduledTask::factory()->create([
            'group_id' => 1,
            'title' => '元のタイトル',
        ]);

        $data = [
            'title' => '更新後のタイトル',
            'description' => '更新後の説明',
            'schedules' => [
                ['type' => 'daily', 'time' => '10:00']
            ],
            'reward' => 200,
            'start_date' => Carbon::now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('batch.scheduled-tasks.update', $scheduledTask->id), $data);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'scheduled-task-updated');

        $this->assertDatabaseHas('scheduled_tasks', [
            'id' => $scheduledTask->id,
            'title' => '更新後のタイトル',
            'reward' => 200,
        ]);
    }

    /** @test */
    public function 管理者はスケジュールタスクを削除できる(): void
    {
        $scheduledTask = ScheduledTask::factory()->create(['group_id' => 1]);

        $response = $this->actingAs($this->admin)
            ->delete(route('batch.scheduled-tasks.destroy', $scheduledTask->id));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'scheduled-task-deleted');

        $this->assertDatabaseMissing('scheduled_tasks', [
            'id' => $scheduledTask->id,
        ]);
    }

    /** @test */
    public function 管理者はスケジュールタスクを一時停止できる(): void
    {
        $scheduledTask = ScheduledTask::factory()->create([
            'group_id' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('batch.scheduled-tasks.pause', $scheduledTask->id));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'scheduled-task-paused');

        $this->assertDatabaseHas('scheduled_tasks', [
            'id' => $scheduledTask->id,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function 管理者はスケジュールタスクを再開できる(): void
    {
        $scheduledTask = ScheduledTask::factory()->create([
            'group_id' => 1,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('batch.scheduled-tasks.resume', $scheduledTask->id));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'scheduled-task-resumed');

        $this->assertDatabaseHas('scheduled_tasks', [
            'id' => $scheduledTask->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function 実行履歴を表示できる(): void
    {
        $scheduledTask = ScheduledTask::factory()->create(['group_id' => 1]);

        $response = $this->actingAs($this->admin)
            ->get(route('batch.scheduled-tasks.history', $scheduledTask->id));

        $response->assertStatus(200);
        $response->assertViewIs('batch.history');
        $response->assertViewHas('scheduledTask');
        $response->assertViewHas('executions');
    }
}