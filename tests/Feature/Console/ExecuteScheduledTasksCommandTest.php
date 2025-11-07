<?php

namespace Tests\Feature\Console;

use Tests\TestCase;
use App\Models\ScheduledTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ExecuteScheduledTasksCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function コマンドが正常に実行される(): void
    {
        $user = User::factory()->create(['group_id' => 1]);

        ScheduledTask::factory()->create([
            'group_id' => 1,
            'title' => 'テストタスク',
            'schedules' => [
                ['type' => 'daily', 'time' => Carbon::now()->format('H:i')]
            ],
            'assigned_user_id' => $user->id,
            'start_date' => Carbon::now()->subDay(),
            'is_active' => true,
        ]);

        $this->artisan('batch:execute-scheduled-tasks')
            ->expectsOutput('スケジュールタスクの実行を開始します...')
            ->assertExitCode(0);

        $this->assertDatabaseHas('scheduled_task_executions', [
            'status' => 'success',
        ]);
    }

    /** @test */
    public function 特定のスケジュールタスクを実行できる(): void
    {
        $user = User::factory()->create(['group_id' => 1]);

        $scheduledTask = ScheduledTask::factory()->create([
            'group_id' => 1,
            'assigned_user_id' => $user->id,
            'schedules' => [
                ['type' => 'daily', 'time' => '09:00']
            ],
            'start_date' => Carbon::now()->subDay(),
            'is_active' => true,
        ]);

        $this->artisan('batch:execute-task', ['id' => $scheduledTask->id])
            ->expectsOutput("スケジュールタスク (ID: {$scheduledTask->id}) を実行します...")
            ->assertExitCode(0);
    }

    /** @test */
    public function スケジュールタスク一覧を表示できる(): void
    {
        ScheduledTask::factory()->count(3)->create([
            'group_id' => 1,
            'is_active' => true,
        ]);

        $this->artisan('batch:list-tasks')
            ->expectsOutput('全スケジュールタスク一覧:')
            ->assertExitCode(0);
    }

    /** @test */
    public function グループ指定でタスク一覧を表示できる(): void
    {
        ScheduledTask::factory()->count(2)->create(['group_id' => 1]);
        ScheduledTask::factory()->create(['group_id' => 2]);

        $this->artisan('batch:list-tasks', ['--group' => 1])
            ->expectsOutput('グループ 1 のスケジュールタスク一覧:')
            ->assertExitCode(0);
    }
}