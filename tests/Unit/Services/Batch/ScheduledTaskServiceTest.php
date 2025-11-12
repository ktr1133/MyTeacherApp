<?php

namespace Tests\Unit\Services\Batch;

use Tests\TestCase;
use App\Services\Batch\ScheduledTaskService;
use App\Repositories\Batch\ScheduledTaskRepositoryInterface;
use App\Repositories\Task\TaskRepositoryInterface;
use App\Models\ScheduledTask;
use App\Models\User;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Carbon\Carbon;

class ScheduledTaskServiceTest extends TestCase
{
    use RefreshDatabase;

    private ScheduledTaskService $service;
    private $scheduledTaskRepository;
    private $taskRepository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scheduledTaskRepository = Mockery::mock(ScheduledTaskRepositoryInterface::class);
        $this->taskRepository = Mockery::mock(TaskRepositoryInterface::class);
        
        $this->service = new ScheduledTaskService(
            $this->scheduledTaskRepository,
            $this->taskRepository
        );

        $this->user = User::factory()->create([
            'group_id' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function スケジュールタスクを作成できる(): void
    {
        $data = [
            'group_id' => 1,
            'title' => 'テストタスク',
            'schedules' => [
                ['type' => 'daily', 'time' => '09:00']
            ],
            'reward' => 100,
            'start_date' => Carbon::now(),
        ];

        $scheduledTask = ScheduledTask::factory()->make($data);

        $this->scheduledTaskRepository
            ->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn($scheduledTask);

        $result = $this->service->createScheduledTask($data);

        $this->assertEquals('テストタスク', $result->title);
    }

    /** @test */
    public function スケジュールタスクを更新できる(): void
    {
        $scheduledTask = ScheduledTask::factory()->make(['id' => 1]);

        $this->scheduledTaskRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($scheduledTask);

        $this->scheduledTaskRepository
            ->shouldReceive('update')
            ->once()
            ->with(1, ['title' => '更新後'])
            ->andReturn(true);

        $result = $this->service->updateScheduledTask(1, ['title' => '更新後']);

        $this->assertTrue($result);
    }

    /** @test */
    public function 存在しないスケジュールタスクの更新は例外をスロー(): void
    {
        $this->scheduledTaskRepository
            ->shouldReceive('findById')
            ->once()
            ->with(9999)
            ->andReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('スケジュールタスクが見つかりません');

        $this->service->updateScheduledTask(9999, ['title' => '更新']);
    }

    /** @test */
    public function スケジュールタスクを削除できる(): void
    {
        $scheduledTask = ScheduledTask::factory()->make(['id' => 1]);

        $this->scheduledTaskRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($scheduledTask);

        $this->scheduledTaskRepository
            ->shouldReceive('delete')
            ->once()
            ->with(1)
            ->andReturn(true);

        $result = $this->service->deleteScheduledTask(1);

        $this->assertTrue($result);
    }

    /** @test */
    public function スケジュールタスクを一時停止できる(): void
    {
        $scheduledTask = ScheduledTask::factory()->make([
            'id' => 1,
            'is_active' => true,
        ]);

        $this->scheduledTaskRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($scheduledTask);

        $this->scheduledTaskRepository
            ->shouldReceive('pause')
            ->once()
            ->with(1)
            ->andReturn(true);

        $result = $this->service->pauseScheduledTask(1);

        $this->assertTrue($result);
    }

    /** @test */
    public function スケジュールタスクを再開できる(): void
    {
        $scheduledTask = ScheduledTask::factory()->make([
            'id' => 1,
            'is_active' => false,
        ]);

        $this->scheduledTaskRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($scheduledTask);

        $this->scheduledTaskRepository
            ->shouldReceive('resume')
            ->once()
            ->with(1)
            ->andReturn(true);

        $result = $this->service->resumeScheduledTask(1);

        $this->assertTrue($result);
    }

    /** @test */
    public function スケジュールタスクを実行してタスクを作成できる(): void
    {
        $now = Carbon::parse('2025-01-15 09:00:00');
        
        $scheduledTask = ScheduledTask::factory()->make([
            'id' => 1,
            'group_id' => 1,
            'title' => 'テストタスク',
            'description' => 'テスト説明',
            'reward' => 100,
            'requires_image' => false,
            'auto_assign' => false,
            'assigned_user_id' => $this->user->id,
            'due_duration_days' => 1,
            'due_duration_hours' => 0,
            'tags' => ['tag1', 'tag2'],
        ]);

        $createdTask = Task::factory()->make([
            'id' => 123,
            'group_id' => 1,
        ]);

        $this->scheduledTaskRepository
            ->shouldReceive('getTasksToExecute')
            ->once()
            ->with($now)
            ->andReturn(collect([$scheduledTask]));

        $this->taskRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($createdTask);

        $this->scheduledTaskRepository
            ->shouldReceive('recordExecution')
            ->once()
            ->with(
                1,
                $now,
                'success',
                123,
                $this->user->id,
                Mockery::any()
            );

        $results = $this->service->executeScheduledTasks($now);

        $this->assertEquals(1, $results['total_processed']);
        $this->assertEquals(1, $results['total_created']);
        $this->assertEquals(0, $results['total_failed']);
    }

    /** @test */
    public function ランダム割り当てが機能する(): void
    {
        $now = Carbon::parse('2025-01-15 09:00:00');
        
        $users = User::factory()->count(3)->create(['group_id' => 1]);
        
        $scheduledTask = ScheduledTask::factory()->make([
            'id' => 1,
            'group_id' => 1,
            'title' => 'テストタスク',
            'reward' => 100,
            'auto_assign' => true,
            'assigned_user_id' => null,
        ]);

        $createdTask = Task::factory()->make(['id' => 123]);

        $this->scheduledTaskRepository
            ->shouldReceive('getTasksToExecute')
            ->once()
            ->andReturn(collect([$scheduledTask]));

        $this->taskRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($createdTask);

        $this->scheduledTaskRepository
            ->shouldReceive('recordExecution')
            ->once();

        $results = $this->service->executeScheduledTasks($now);

        $this->assertEquals(1, $results['total_created']);
    }

    /** @test */
    public function 祝日をスキップできる(): void
    {
        $now = Carbon::parse('2025-01-01 09:00:00'); // 元日（祝日）
        
        $scheduledTask = ScheduledTask::factory()->make([
            'id' => 1,
            'skip_holidays' => true,
        ]);

        $this->scheduledTaskRepository
            ->shouldReceive('getTasksToExecute')
            ->once()
            ->andReturn(collect([$scheduledTask]));

        $this->scheduledTaskRepository
            ->shouldReceive('recordExecution')
            ->once()
            ->with(
                1,
                $now,
                'skipped',
                null,
                null,
                Mockery::on(function ($message) {
                    return str_contains($message, '祝日のためスキップ');
                })
            );

        $results = $this->service->executeScheduledTasks($now);

        $this->assertEquals(1, $results['total_skipped']);
    }

    /** @test */
    public function 未完了タスクを削除できる(): void
    {
        $now = Carbon::parse('2025-01-15 09:00:00');
        
        $scheduledTask = ScheduledTask::factory()->make([
            'id' => 1,
            'group_id' => 1,
            'title' => 'テストタスク',
            'reward' => 100,
            'auto_assign' => false,
            'assigned_user_id' => $this->user->id,
            'delete_incomplete_previous' => true,
        ]);

        $previousTask = Task::factory()->make([
            'id' => 100,
            'completed_at' => null,
        ]);

        $createdTask = Task::factory()->make(['id' => 123]);

        $this->scheduledTaskRepository
            ->shouldReceive('getTasksToExecute')
            ->once()
            ->andReturn(collect([$scheduledTask]));

        $this->scheduledTaskRepository
            ->shouldReceive('getLastExecution')
            ->once()
            ->with(1)
            ->andReturn((object)[
                'task_id' => 100,
                'task' => $previousTask,
            ]);

        $this->taskRepository
            ->shouldReceive('findById')
            ->once()
            ->with(100)
            ->andReturn($previousTask);

        $this->taskRepository
            ->shouldReceive('delete')
            ->once()
            ->with(100)
            ->andReturn(true);

        $this->taskRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($createdTask);

        $this->scheduledTaskRepository
            ->shouldReceive('recordExecution')
            ->once();

        $results = $this->service->executeScheduledTasks($now);

        $this->assertEquals(1, $results['total_created']);
    }
}