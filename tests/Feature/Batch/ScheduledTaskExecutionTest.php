<?php

use App\Models\User;
use App\Models\ScheduledGroupTask;
use App\Models\Task;
use App\Models\Holiday;
use App\Models\Group;
use App\Services\Batch\ScheduledTaskServiceInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * スケジュールタスク実行の統合テスト
 * 
 * executeScheduledTasks()メソッドの実際の動作を検証
 */

beforeEach(function () {
    // グループを先に作成（外部キー制約対応）
    $this->group = Group::factory()->create(['id' => 1]);

    $this->user = User::factory()->create([
        'group_id' => 1,
        'is_admin' => false,
    ]);

    $this->admin = User::factory()->create([
        'group_id' => 1,
        'is_admin' => true,
    ]);

    $this->service = app(ScheduledTaskServiceInterface::class);
});

test('毎日9時のスケジュールタスクが正常に実行される', function (): void
{
    // 2025-01-15 09:00 (Asia/Tokyo) に実行 = UTC 00:00
    $now = Carbon::parse('2025-01-15 00:00:00', 'UTC');

    // 毎日9時のスケジュールタスクを作成
    $scheduledTask = ScheduledGroupTask::factory()->create([
        'group_id' => 1,
        'title' => '朝のタスク',
        'description' => 'テスト説明',
        'reward' => 100,
        'schedules' => [
            ['type' => 'daily', 'time' => '09:00']
        ],
        'created_by' => $this->admin->id,
        'assigned_user_id' => $this->user->id,
        'auto_assign' => false,  // 自動割当無効
        'start_date' => Carbon::parse('2025-01-01'),
        'is_active' => true,
    ]);

    $results = $this->service->executeScheduledTasks($now);

    $this->assertEquals(1, $results['success']);
        $this->assertEquals(0, $results['failed']);
        $this->assertEquals(0, $results['skipped']);

    // タスクが作成されたことを確認
    expect($this->user->id)->toBeInt();
    $this->assertDatabaseHas('tasks', [
        'user_id' => $this->user->id,
        'title' => '朝のタスク',
        'reward' => 100,
    ]);

    // 実行履歴が記録されたことを確認
    $this->assertDatabaseHas('scheduled_task_executions', [
        'scheduled_task_id' => $scheduledTask->id,
        'status' => 'success',
    ]);
});

test('時刻が一致しないスケジュールタスクは実行されない', function (): void
{
    $now = Carbon::parse('2025-01-15 00:00:00', 'UTC');

    ScheduledGroupTask::factory()->create([
            'group_id' => 1,
            'title' => '10時のタスク',
            'schedules' => [
                ['type' => 'daily', 'time' => '10:00']
            ],
            'created_by' => $this->admin->id,
            'assigned_user_id' => $this->user->id,
            'auto_assign' => false,
            'start_date' => Carbon::parse('2025-01-01'),
            'is_active' => true,
        ]);

    $results = $this->service->executeScheduledTasks($now);

    $this->assertEquals(0, $results['success']);
    $this->assertEquals(0, $results['failed']);
    $this->assertEquals(1, $results['skipped']);

    $this->assertDatabaseMissing('tasks', [
        'title' => '10時のタスク',
    ]);
});

test('毎週水曜日のスケジュールタスクが水曜日に実行される', function (): void
{
    $now = Carbon::parse('2025-01-15 00:00:00', 'UTC'); // 水曜日 09:00 JST

    ScheduledGroupTask::factory()->create([
        'group_id' => 1,
        'title' => '水曜日のタスク',
        'schedules' => [
            ['type' => 'weekly', 'days' => [3], 'time' => '09:00'] // 水曜日
        ],
        'created_by' => $this->admin->id,
        'assigned_user_id' => $this->user->id,
        'auto_assign' => false,
        'start_date' => Carbon::parse('2025-01-01'),
        'is_active' => true,
    ]);

    $results = $this->service->executeScheduledTasks($now);

    expect($results['success'])->toBe(1);
    $this->assertDatabaseHas('tasks', [
        'title' => '水曜日のタスク',
    ]);
});

test('毎週水曜日のスケジュールタスクが月曜日には実行されない', function (): void
{
    $now = Carbon::parse('2025-01-13 00:00:00', 'UTC'); // 月曜日 09:00 JST

    ScheduledGroupTask::factory()->create([
        'group_id' => 1,
        'title' => '水曜日のタスク',
        'schedules' => [
            ['type' => 'weekly', 'days' => [3], 'time' => '09:00'] // 水曜日
        ],
        'created_by' => $this->admin->id,
        'assigned_user_id' => $this->user->id,
        'auto_assign' => false,
        'start_date' => Carbon::parse('2025-01-01'),
        'is_active' => true,
    ]);

    $results = $this->service->executeScheduledTasks($now);

    expect($results['success'])->toBe(0);
    $this->assertDatabaseMissing('tasks', [
        'title' => '水曜日のタスク',
    ]);
});

test('毎月15日のスケジュールタスクが15日に実行される', function (): void
{
    $now = Carbon::parse('2025-01-15 00:00:00', 'UTC');    
    
    ScheduledGroupTask::factory()->create([
        'group_id' => 1,
        'title' => '毎月15日のタスク',
        'schedules' => [
            ['type' => 'monthly', 'dates' => [15], 'time' => '09:00']
        ],
        'created_by' => $this->admin->id,
        'assigned_user_id' => $this->user->id,
        'auto_assign' => false,
        'start_date' => Carbon::parse('2025-01-01'),
        'is_active' => true,
    ]);

    $results = $this->service->executeScheduledTasks($now);

    expect($results['success'])->toBe(1);
    $this->assertDatabaseHas('tasks', [
        'title' => '毎月15日のタスク',
    ]);
});

test('非アクティブなスケジュールタスクは実行されない', function (): void
{
    $now = Carbon::parse('2025-01-15 00:00:00', 'UTC');

    ScheduledGroupTask::factory()->create([
            'group_id' => 1,
            'title' => '停止中のタスク',
            'schedules' => [
                ['type' => 'daily', 'time' => '09:00']
            ],
            'created_by' => $this->admin->id,
            'assigned_user_id' => $this->user->id,
            'auto_assign' => false,
            'start_date' => Carbon::parse('2025-01-01'),
            'is_active' => false,
        ]);

    $results = $this->service->executeScheduledTasks($now);

    expect($results['success'])->toBe(0);
    $this->assertDatabaseMissing('tasks', [
        'title' => '停止中のタスク',
    ]);
});

test('祝日にスキップ設定のタスクは祝日に実行されない', function (): void
{
    $now = Carbon::parse('2025-01-01 00:00:00', 'UTC'); // 元日 09:00 JST

    // 祝日データを作成
    Holiday::create([
        'date' => Carbon::parse('2025-01-01'),
        'name' => '元日',
    ]);
    
    ScheduledGroupTask::factory()->create([
        'group_id' => 1,
        'title' => '祝日スキップタスク',
        'schedules' => [
            ['type' => 'daily', 'time' => '09:00']
        ],
        'created_by' => $this->admin->id,
        'assigned_user_id' => $this->user->id,
        'auto_assign' => false,
        'start_date' => Carbon::parse('2024-12-01'),
        'skip_holidays' => true,
        'is_active' => true,
    ]);

    $results = $this->service->executeScheduledTasks($now);

    expect($results['success'])->toBe(0);
    expect($results['skipped'])->toBe(1);
    $this->assertDatabaseMissing('tasks', [
        'title' => '祝日スキップタスク',
    ]);

    // スキップ履歴が記録されていることを確認
    $this->assertDatabaseHas('scheduled_task_executions', [
        'status' => 'skipped',
        'note' => '祝日のためスキップ',
    ]);
});

test('祝日スキップ無効のタスクは祝日でも実行される', function (): void
{
    $now = Carbon::parse('2025-01-01 00:00:00', 'UTC'); // 元日 09:00 JST

    Holiday::create([
        'date' => Carbon::parse('2025-01-01'),
        'name' => '元日',
    ]);

    ScheduledGroupTask::factory()->create([
            'group_id' => 1,
            'title' => '祝日も実行タスク',
            'schedules' => [
                ['type' => 'daily', 'time' => '09:00']
            ],
            'created_by' => $this->admin->id,
            'assigned_user_id' => $this->user->id,
            'auto_assign' => false,
            'start_date' => Carbon::parse('2024-12-01'),
            'skip_holidays' => false,
            'is_active' => true,
        ]);

    $results = $this->service->executeScheduledTasks($now);

    expect($results['success'])->toBe(1);
    $this->assertDatabaseHas('tasks', [
        'title' => '祝日も実行タスク',
    ]);
});

test('複数のスケジュールタスクが同時に実行される', function (): void
{
    $now = Carbon::parse('2025-01-15 00:00:00', 'UTC');

    ScheduledGroupTask::factory()->create([
            'group_id' => 1,
            'title' => 'タスク1',
            'schedules' => [
                ['type' => 'daily', 'time' => '09:00']
            ],
            'created_by' => $this->admin->id,
            'assigned_user_id' => $this->user->id,
            'auto_assign' => false,
            'start_date' => Carbon::parse('2025-01-01'),
            'is_active' => true,
        ]);

    ScheduledGroupTask::factory()->create([
            'group_id' => 1,
            'title' => 'タスク2',
            'schedules' => [
                ['type' => 'daily', 'time' => '09:00']
            ],
            'created_by' => $this->admin->id,
            'assigned_user_id' => $this->user->id,
            'auto_assign' => false,
            'start_date' => Carbon::parse('2025-01-01'),
            'is_active' => true,
        ]);

    ScheduledGroupTask::factory()->create([
        'group_id' => 1,
        'title' => 'タスク3',
        'schedules' => [
            ['type' => 'daily', 'time' => '09:00']
        ],
        'created_by' => $this->admin->id,
        'assigned_user_id' => $this->user->id,
        'auto_assign' => false,
        'start_date' => Carbon::parse('2025-01-01'),
        'is_active' => true,
    ]);

    $results = $this->service->executeScheduledTasks($now);

    expect($results['success'])->toBe(3);
    expect($results['failed'])->toBe(0);
    expect($results['skipped'])->toBe(0);

    $this->assertDatabaseHas('tasks', ['title' => 'タスク1']);
    $this->assertDatabaseHas('tasks', ['title' => 'タスク2']);
    $this->assertDatabaseHas('tasks', ['title' => 'タスク3']);
});

test('タグが正しく設定されてnullエラーが発生しない', function (): void
{
    $now = Carbon::parse('2025-01-15 00:00:00', 'UTC');

    $scheduledTask = ScheduledGroupTask::factory()->create([
            'group_id' => 1,
            'title' => 'タグなしタスク',
            'schedules' => [
                ['type' => 'daily', 'time' => '09:00']
            ],
            'created_by' => $this->admin->id,
            'assigned_user_id' => $this->user->id,
            'auto_assign' => false,
            'start_date' => Carbon::parse('2025-01-01'),
            'is_active' => true,
        ]);

    $results = $this->service->executeScheduledTasks($now);

    expect($results['success'])->toBe(1);
    expect($results['failed'])->toBe(0);
});

test('エラーログが正しく記録される', function (): void
{
    Log::spy();

    $now = Carbon::parse('2025-01-15 00:00:00', 'UTC');

    ScheduledGroupTask::factory()->create([
            'group_id' => 1,
            'title' => '正常タスク',
            'schedules' => [
                ['type' => 'daily', 'time' => '09:00']
            ],
            'created_by' => $this->admin->id,
            'assigned_user_id' => $this->user->id,
            'auto_assign' => false,
            'start_date' => Carbon::parse('2025-01-01'),
            'is_active' => true,
        ]);

    $results = $this->service->executeScheduledTasks($now);

    Log::shouldHaveReceived('info')
        ->with('Scheduled tasks executed successfully', \Mockery::type('array'))
        ->once();
});

test('開始日前のスケジュールタスクは実行されない', function (): void
{
    $now = Carbon::parse('2025-01-15 00:00:00', 'UTC');

    ScheduledGroupTask::factory()->create([
            'group_id' => 1,
            'title' => '未来のタスク',
            'schedules' => [
                ['type' => 'daily', 'time' => '09:00']
            ],
            'created_by' => $this->admin->id,
            'assigned_user_id' => $this->user->id,
            'auto_assign' => false,
            'start_date' => Carbon::parse('2025-01-20'),
            'is_active' => true,
        ]);

    $results = $this->service->executeScheduledTasks($now);

    expect($results['success'])->toBe(0);
    $this->assertDatabaseMissing('tasks', [
        'title' => '未来のタスク',
    ]);
});

test('終了日を過ぎたスケジュールタスクは実行されない', function (): void
{
    $now = Carbon::parse('2025-01-15 00:00:00', 'UTC');

    ScheduledGroupTask::factory()->create([
            'group_id' => 1,
            'title' => '終了したタスク',
            'schedules' => [
                ['type' => 'daily', 'time' => '09:00']
            ],
            'created_by' => $this->admin->id,
            'assigned_user_id' => $this->user->id,
            'auto_assign' => false,
            'start_date' => Carbon::parse('2025-01-01'),
            'end_date' => Carbon::parse('2025-01-10'),
            'is_active' => true,
        ]);

    $results = $this->service->executeScheduledTasks($now);

    expect($results['success'])->toBe(0);
    $this->assertDatabaseMissing('tasks', [
        'title' => '終了したタスク',
    ]);
});

test('期日が正しく設定される', function (): void
{
    $now = Carbon::parse('2025-01-15 00:00:00', 'UTC');

    ScheduledGroupTask::factory()->create([
            'group_id' => 1,
            'title' => '期日設定タスク',
            'schedules' => [
                ['type' => 'daily', 'time' => '09:00']
            ],
            'created_by' => $this->admin->id,
            'assigned_user_id' => $this->user->id,
            'auto_assign' => false,
            'start_date' => Carbon::parse('2025-01-01'),
            'due_duration_days' => 2,
            'due_duration_hours' => 3,
            'is_active' => true,
        ]);

    $results = $this->service->executeScheduledTasks($now);

    expect($results['success'])->toBe(1);

    $task = Task::where('title', '期日設定タスク')->first();
    $expectedDueDate = $now->copy()->addDays(2)->addHours(3);
    
    expect($task)->not->toBeNull();
    expect($task->due_date->format('Y-m-d H:i'))->toBe($expectedDueDate->format('Y-m-d H:i'));
});
