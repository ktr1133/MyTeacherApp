<?php

namespace Tests\Unit\Repositories\Batch;

use Tests\TestCase;
use App\Repositories\Batch\ScheduledTaskEloquentRepository;
use App\Models\ScheduledGroupTask as ScheduledTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ScheduledTaskEloquentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ScheduledTaskEloquentRepository $repository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new ScheduledTaskEloquentRepository();
        
        // テストユーザーを作成
        $this->user = User::factory()->create([
            'group_id' => 1,
        ]);
    }

    /** @test */
    public function スケジュールタスクを作成できる(): void
    {
        $data = [
            'group_id' => 1,
            'title' => 'テストタスク',
            'description' => 'テスト説明',
            'schedules' => [
                ['type' => 'daily', 'time' => '09:00']
            ],
            'reward' => 100,
            'requires_image' => false,
            'auto_assign' => false,
            'start_date' => Carbon::now(),
            'is_active' => true,
        ];

        $scheduledTask = $this->repository->create($data);

        $this->assertInstanceOf(ScheduledTask::class, $scheduledTask);
        $this->assertEquals('テストタスク', $scheduledTask->title);
        $this->assertEquals(100, $scheduledTask->reward);
        $this->assertCount(1, $scheduledTask->schedules);
    }

    /** @test */
    public function IDでスケジュールタスクを取得できる(): void
    {
        $scheduledTask = ScheduledTask::factory()->create([
            'group_id' => 1,
            'title' => '取得テスト',
        ]);

        $found = $this->repository->findById($scheduledTask->id);

        $this->assertNotNull($found);
        $this->assertEquals('取得テスト', $found->title);
    }

    /** @test */
    public function 存在しないIDの場合nullを返す(): void
    {
        $found = $this->repository->findById(9999);

        $this->assertNull($found);
    }

    /** @test */
    public function グループIDでスケジュールタスクを取得できる(): void
    {
        ScheduledTask::factory()->count(3)->create(['group_id' => 1]);
        ScheduledTask::factory()->count(2)->create(['group_id' => 2]);

        $tasks = $this->repository->getByGroupId(1);

        $this->assertCount(3, $tasks);
        $this->assertTrue($tasks->every(fn($task) => $task->group_id === 1));
    }

    /** @test */
    public function 有効なスケジュールタスクのみ取得できる(): void
    {
        ScheduledTask::factory()->create([
            'group_id' => 1,
            'is_active' => true,
        ]);
        ScheduledTask::factory()->create([
            'group_id' => 1,
            'is_active' => false,
        ]);

        $activeTasks = $this->repository->getAllActive();

        $this->assertCount(1, $activeTasks);
        $this->assertTrue($activeTasks->first()->is_active);
    }

    /** @test */
    public function 指定時刻に実行すべきスケジュールタスクを取得できる_毎日(): void
    {
        $now = Carbon::parse('2025-01-15 09:00:00'); // 水曜日

        ScheduledTask::factory()->create([
            'group_id' => 1,
            'schedules' => [
                ['type' => 'daily', 'time' => '09:00']
            ],
            'start_date' => Carbon::parse('2025-01-01'),
            'is_active' => true,
        ]);

        ScheduledTask::factory()->create([
            'group_id' => 1,
            'schedules' => [
                ['type' => 'daily', 'time' => '10:00']
            ],
            'start_date' => Carbon::parse('2025-01-01'),
            'is_active' => true,
        ]);

        $tasks = $this->repository->getTasksToExecute($now);

        $this->assertCount(1, $tasks);
        $this->assertEquals('09:00', $tasks->first()->schedules[0]['time']);
    }

    /** @test */
    public function 指定時刻に実行すべきスケジュールタスクを取得できる_毎週(): void
    {
        $now = Carbon::parse('2025-01-15 09:00:00'); // 水曜日 (3)

        ScheduledTask::factory()->create([
            'group_id' => 1,
            'schedules' => [
                ['type' => 'weekly', 'days' => [3], 'time' => '09:00'] // 水曜日
            ],
            'start_date' => Carbon::parse('2025-01-01'),
            'is_active' => true,
        ]);

        ScheduledTask::factory()->create([
            'group_id' => 1,
            'schedules' => [
                ['type' => 'weekly', 'days' => [1], 'time' => '09:00'] // 月曜日
            ],
            'start_date' => Carbon::parse('2025-01-01'),
            'is_active' => true,
        ]);

        $tasks = $this->repository->getTasksToExecute($now);

        $this->assertCount(1, $tasks);
    }

    /** @test */
    public function 指定時刻に実行すべきスケジュールタスクを取得できる_毎月(): void
    {
        $now = Carbon::parse('2025-01-15 09:00:00'); // 15日

        ScheduledTask::factory()->create([
            'group_id' => 1,
            'schedules' => [
                ['type' => 'monthly', 'dates' => [15], 'time' => '09:00']
            ],
            'start_date' => Carbon::parse('2025-01-01'),
            'is_active' => true,
        ]);

        ScheduledTask::factory()->create([
            'group_id' => 1,
            'schedules' => [
                ['type' => 'monthly', 'dates' => [20], 'time' => '09:00']
            ],
            'start_date' => Carbon::parse('2025-01-01'),
            'is_active' => true,
        ]);

        $tasks = $this->repository->getTasksToExecute($now);

        $this->assertCount(1, $tasks);
    }

    /** @test */
    public function 開始日前のタスクは取得されない(): void
    {
        $now = Carbon::parse('2025-01-15 09:00:00');

        ScheduledTask::factory()->create([
            'group_id' => 1,
            'schedules' => [
                ['type' => 'daily', 'time' => '09:00']
            ],
            'start_date' => Carbon::parse('2025-01-20'), // 未来
            'is_active' => true,
        ]);

        $tasks = $this->repository->getTasksToExecute($now);

        $this->assertCount(0, $tasks);
    }

    /** @test */
    public function 終了日を過ぎたタスクは取得されない(): void
    {
        $now = Carbon::parse('2025-01-15 09:00:00');

        ScheduledTask::factory()->create([
            'group_id' => 1,
            'schedules' => [
                ['type' => 'daily', 'time' => '09:00']
            ],
            'start_date' => Carbon::parse('2025-01-01'),
            'end_date' => Carbon::parse('2025-01-10'), // 過去
            'is_active' => true,
        ]);

        $tasks = $this->repository->getTasksToExecute($now);

        $this->assertCount(0, $tasks);
    }

    /** @test */
    public function スケジュールタスクを更新できる(): void
    {
        $scheduledTask = ScheduledTask::factory()->create([
            'title' => '元のタイトル',
            'reward' => 100,
        ]);

        $updated = $this->repository->update($scheduledTask->id, [
            'title' => '更新後のタイトル',
            'reward' => 200,
        ]);

        $this->assertTrue($updated);
        
        $scheduledTask->refresh();
        $this->assertEquals('更新後のタイトル', $scheduledTask->title);
        $this->assertEquals(200, $scheduledTask->reward);
    }

    /** @test */
    public function スケジュールタスクを削除できる(): void
    {
        $scheduledTask = ScheduledTask::factory()->create();

        $deleted = $this->repository->delete($scheduledTask->id);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('scheduled_tasks', [
            'id' => $scheduledTask->id,
        ]);
    }

    /** @test */
    public function スケジュールタスクを一時停止できる(): void
    {
        $scheduledTask = ScheduledTask::factory()->create([
            'is_active' => true,
        ]);

        $paused = $this->repository->pause($scheduledTask->id);

        $this->assertTrue($paused);
        $scheduledTask->refresh();
        $this->assertFalse($scheduledTask->is_active);
    }

    /** @test */
    public function スケジュールタスクを再開できる(): void
    {
        $scheduledTask = ScheduledTask::factory()->create([
            'is_active' => false,
        ]);

        $resumed = $this->repository->resume($scheduledTask->id);

        $this->assertTrue($resumed);
        $scheduledTask->refresh();
        $this->assertTrue($scheduledTask->is_active);
    }

    /** @test */
    public function 実行履歴を記録できる(): void
    {
        $scheduledTask = ScheduledTask::factory()->create();

        $execution = $this->repository->recordExecution(
            $scheduledTask->id,
            Carbon::now(),
            'success',
            123,
            $this->user->id
        );

        $this->assertDatabaseHas('scheduled_task_executions', [
            'scheduled_task_id' => $scheduledTask->id,
            'status' => 'success',
            'task_id' => 123,
            'assigned_user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function 実行履歴を取得できる(): void
    {
        $scheduledTask = ScheduledTask::factory()->create();

        // 5件の履歴を作成
        foreach (range(1, 5) as $i) {
            $this->repository->recordExecution(
                $scheduledTask->id,
                Carbon::now()->subDays($i),
                'success',
                $i
            );
        }

        $executions = $this->repository->getExecutionHistory($scheduledTask->id, 3);

        $this->assertCount(3, $executions);
        // 新しい順に取得されることを確認
        $this->assertEquals(1, $executions->first()->task_id);
    }

    /** @test */
    public function 最後の実行を取得できる(): void
    {
        $scheduledTask = ScheduledTask::factory()->create();

        $this->repository->recordExecution(
            $scheduledTask->id,
            Carbon::now()->subHours(2),
            'success',
            1
        );

        $lastExecution = $this->repository->recordExecution(
            $scheduledTask->id,
            Carbon::now(),
            'success',
            2
        );

        $found = $this->repository->getLastExecution($scheduledTask->id);

        $this->assertEquals($lastExecution->id, $found->id);
        $this->assertEquals(2, $found->task_id);
    }
}