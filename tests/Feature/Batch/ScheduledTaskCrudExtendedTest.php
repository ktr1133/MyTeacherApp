<?php

use App\Models\User;
use App\Models\ScheduledGroupTask;
use App\Models\Group;
use Carbon\Carbon;

/**
 * スケジュールタスクCRUD操作の拡張テスト
 * 
 * 担当者設定、スケジュール設定（毎週・毎月）、編集画面の既存値復元を検証
 */

beforeEach(function () {
    // グループを先に作成（外部キー制約対応）
    $this->group = Group::factory()->create(['id' => 1]);

    // 管理者ユーザー
    $this->admin = User::factory()->create([
        'group_id' => 1,
        'is_admin' => true,
        'group_edit_flg' => true,
        'timezone' => 'Asia/Tokyo',
    ]);

    // 一般メンバー
    $this->member = User::factory()->create([
        'group_id' => 1,
        'is_admin' => false,
        'group_edit_flg' => false,
        'timezone' => 'Asia/Tokyo',
    ]);
});

// ============================================================
// 担当者設定のテスト（今回の修正内容）
// ============================================================

test('固定担当者を指定してスケジュールタスクを作成できる', function (): void
{
    $targetUser = User::factory()->create(['group_id' => 1]);

    $data = [
        'group_id' => 1,
        'title' => '固定担当者タスク',
        'description' => '特定ユーザーに割り当てる',
        'schedules' => [
            ['type' => 'daily', 'time' => '09:00']
        ],
        'reward' => 100,
        'start_date' => Carbon::now()->format('Y-m-d'),
        'auto_assign' => false,  // 自動割当OFF
        'assigned_user_id' => $targetUser->id,  // 担当者指定
        'created_by' => $this->admin->id,
    ];

    $response = $this->actingAs($this->admin)
        ->post(route('batch.scheduled-tasks.store'), $data);

    $response->assertRedirect();

    $this->assertDatabaseHas('scheduled_group_tasks', [
        'title' => '固定担当者タスク',
        'auto_assign' => false,
        'assigned_user_id' => $targetUser->id,
    ]);
});

test('自動割当ONでスケジュールタスクを作成できる', function (): void
{
    $data = [
        'group_id' => 1,
        'title' => '自動割当タスク',
        'description' => 'ランダムに割り当てる',
        'schedules' => [
            ['type' => 'daily', 'time' => '09:00']
        ],
        'reward' => 100,
        'start_date' => Carbon::now()->format('Y-m-d'),
        'auto_assign' => true,  // 自動割当ON
        'assigned_user_id' => null,  // 担当者なし
        'created_by' => $this->admin->id,
    ];

    $response = $this->actingAs($this->admin)
        ->post(route('batch.scheduled-tasks.store'), $data);

    $response->assertRedirect();

    $this->assertDatabaseHas('scheduled_group_tasks', [
        'title' => '自動割当タスク',
        'auto_assign' => true,
        'assigned_user_id' => null,
    ]);
});

test('担当者を変更してスケジュールタスクを更新できる', function (): void
{
    $oldUser = User::factory()->create(['group_id' => 1]);
    $newUser = User::factory()->create(['group_id' => 1]);

    $scheduledTask = ScheduledGroupTask::factory()->create([
        'group_id' => 1,
        'title' => '担当者変更テスト',
        'auto_assign' => false,
        'assigned_user_id' => $oldUser->id,
        'created_by' => $this->admin->id,
        'start_date' => Carbon::now(),
    ]);

    $data = [
        'title' => '担当者変更テスト',
        'schedules' => $scheduledTask->schedules,
        'reward' => 200,
        'start_date' => Carbon::now()->format('Y-m-d'),
        'auto_assign' => false,
        'assigned_user_id' => $newUser->id,  // 担当者変更
    ];

    $response = $this->actingAs($this->admin)
        ->put(route('batch.scheduled-tasks.update', $scheduledTask->id), $data);

    $response->assertRedirect();

    $this->assertDatabaseHas('scheduled_group_tasks', [
        'id' => $scheduledTask->id,
        'assigned_user_id' => $newUser->id,
    ]);
});

test('固定担当者から自動割当に変更できる', function (): void
{
    $user = User::factory()->create(['group_id' => 1]);

    $scheduledTask = ScheduledGroupTask::factory()->create([
        'group_id' => 1,
        'title' => '割当方式変更テスト',
        'auto_assign' => false,
        'assigned_user_id' => $user->id,
        'created_by' => $this->admin->id,
        'start_date' => Carbon::now(),
    ]);

    $data = [
        'title' => '割当方式変更テスト',
        'schedules' => $scheduledTask->schedules,
        'reward' => 200,
        'start_date' => Carbon::now()->format('Y-m-d'),
        'auto_assign' => true,  // 自動割当に変更
        'assigned_user_id' => null,
    ];

    $response = $this->actingAs($this->admin)
        ->put(route('batch.scheduled-tasks.update', $scheduledTask->id), $data);

    $response->assertRedirect();

    $this->assertDatabaseHas('scheduled_group_tasks', [
        'id' => $scheduledTask->id,
        'auto_assign' => true,
        'assigned_user_id' => null,
    ]);
});

// ============================================================
// スケジュール設定のテスト（毎週・毎月の曜日/日付選択）
// ============================================================

test('毎週複数曜日を指定してスケジュールタスクを作成できる', function (): void
{
    $data = [
        'group_id' => 1,
        'title' => '毎週月水金タスク',
        'description' => '月・水・金に実行',
        'schedules' => [
            [
                'type' => 'weekly',
                'days' => [1, 3, 5],  // 月・水・金
                'time' => '09:00'
            ]
        ],
        'reward' => 100,
        'start_date' => Carbon::now()->format('Y-m-d'),
        'assigned_user_id' => $this->member->id,
        'created_by' => $this->admin->id,
    ];

    $response = $this->actingAs($this->admin)
        ->post(route('batch.scheduled-tasks.store'), $data);

    $response->assertRedirect();

    $this->assertDatabaseHas('scheduled_group_tasks', [
        'title' => '毎週月水金タスク',
    ]);

    // スケジュール設定を検証
    $task = ScheduledGroupTask::where('title', '毎週月水金タスク')->first();
    $schedules = $task->schedules;
    expect($schedules[0]['type'])->toBe('weekly');
    expect($schedules[0]['days'])->toEqual([1, 3, 5]);
    expect($schedules[0]['time'])->toBe('09:00');
});

test('毎月複数日付を指定してスケジュールタスクを作成できる', function (): void
{
    $data = [
        'group_id' => 1,
        'title' => '毎月1日15日末日タスク',
        'description' => '月初・中旬・月末に実行',
        'schedules' => [
            [
                'type' => 'monthly',
                'dates' => [1, 15, 31],  // 1日・15日・31日（-1は末日だがバリデーションで拒否される可能性）
                'time' => '10:00'
            ]
        ],
        'reward' => 150,
        'start_date' => Carbon::now()->format('Y-m-d'),
        'assigned_user_id' => $this->member->id,
        'created_by' => $this->admin->id,
    ];

    $response = $this->actingAs($this->admin)
        ->post(route('batch.scheduled-tasks.store'), $data);

    $response->assertRedirect();
    
    // エラーメッセージがないことを確認
    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('scheduled_group_tasks', [
        'title' => '毎月1日15日末日タスク',
    ]);

    // スケジュール設定を検証
    $task = ScheduledGroupTask::where('title', '毎月1日15日末日タスク')->first();
    $schedules = $task->schedules;
    expect($schedules[0]['type'])->toBe('monthly');
    expect($schedules[0]['dates'])->toEqual([1, 15, 31]);
    expect($schedules[0]['time'])->toBe('10:00');
});

test('毎週のスケジュールを編集できる', function (): void
{
    $scheduledTask = ScheduledGroupTask::factory()->create([
        'group_id' => 1,
        'title' => '週次タスク',
        'schedules' => [
            ['type' => 'weekly', 'days' => [1, 3], 'time' => '09:00']  // 元は月・水
        ],
        'created_by' => $this->admin->id,
        'start_date' => Carbon::now(),
    ]);

    $data = [
        'title' => '週次タスク',
        'schedules' => [
            ['type' => 'weekly', 'days' => [2, 4, 6], 'time' => '10:00']  // 火・木・土に変更
        ],
        'reward' => 200,
        'start_date' => Carbon::now()->format('Y-m-d'),
    ];

    $response = $this->actingAs($this->admin)
        ->put(route('batch.scheduled-tasks.update', $scheduledTask->id), $data);

    $response->assertRedirect();

    // スケジュール設定が更新されたことを確認
    $task = ScheduledGroupTask::find($scheduledTask->id);
    $schedules = $task->schedules;
    expect($schedules[0]['type'])->toBe('weekly');
    expect($schedules[0]['days'])->toEqual([2, 4, 6]);
    expect($schedules[0]['time'])->toBe('10:00');
});

// ============================================================
// 編集画面の既存値復元テスト
// ============================================================

test('編集画面で既存の担当者が復元される', function (): void
{
    $assignedUser = User::factory()->create(['group_id' => 1]);

    $scheduledTask = ScheduledGroupTask::factory()->create([
        'group_id' => 1,
        'title' => '既存値復元テスト',
        'auto_assign' => false,
        'assigned_user_id' => $assignedUser->id,
        'created_by' => $this->admin->id,
        'start_date' => Carbon::now(),
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('batch.scheduled-tasks.edit', $scheduledTask->id));

    $response->assertStatus(200);
    $response->assertViewHas('scheduledTask', function ($task) use ($assignedUser) {
        return $task->assigned_user_id === $assignedUser->id;
    });
});

test('編集画面で既存のスケジュール設定が復元される', function (): void
{
    $scheduledTask = ScheduledGroupTask::factory()->create([
        'group_id' => 1,
        'title' => 'スケジュール復元テスト',
        'schedules' => [
            ['type' => 'weekly', 'days' => [1, 3, 5], 'time' => '09:00']
        ],
        'created_by' => $this->admin->id,
        'start_date' => Carbon::now(),
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('batch.scheduled-tasks.edit', $scheduledTask->id));

    $response->assertStatus(200);
    $response->assertViewHas('scheduledTask', function ($task) {
        $schedules = $task->schedules;
        return $schedules[0]['type'] === 'weekly' 
            && count(array_intersect($schedules[0]['days'], [1, 3, 5])) === 3
            && $schedules[0]['time'] === '09:00';
    });
});

test('編集画面で自動割当の既存値が復元される', function (): void
{
    $scheduledTask = ScheduledGroupTask::factory()->create([
        'group_id' => 1,
        'title' => '自動割当復元テスト',
        'auto_assign' => true,
        'assigned_user_id' => null,
        'created_by' => $this->admin->id,
        'start_date' => Carbon::now(),
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('batch.scheduled-tasks.edit', $scheduledTask->id));

    $response->assertStatus(200);
    $response->assertViewHas('scheduledTask', function ($task) {
        return $task->auto_assign === true && $task->assigned_user_id === null;
    });
});
