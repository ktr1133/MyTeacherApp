<?php

use App\Models\User;
use App\Models\ScheduledGroupTask;
use App\Models\Group;

/**
 * スケジュールタスク一覧表示のテスト
 * 
 * 担当者表示ロジック（name/username fallback）の検証
 */

beforeEach(function () {
    // グループを先に作成（外部キー制約対応）
    $this->group = Group::factory()->create(['id' => 1]);

    $this->admin = User::factory()->create([
        'group_id' => 1,
        'is_admin' => true,
        'group_edit_flg' => true,
        'name' => '管理者',
        'username' => 'admin_user',
    ]);

    $this->member = User::factory()->create([
        'group_id' => 1,
        'is_admin' => false,
        'group_edit_flg' => false,
        'name' => 'メンバー1',
        'username' => 'member1',
    ]);
});

test('固定担当者のnameが一覧に表示される', function (): void
{
    $assignedUser = User::factory()->create([
        'group_id' => 1,
        'name' => '田中太郎',
        'username' => 'tanaka',
    ]);

    $scheduledTask = ScheduledGroupTask::factory()->create([
        'group_id' => 1,
        'title' => '固定担当者タスク',
        'auto_assign' => false,
        'assigned_user_id' => $assignedUser->id,
        'created_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('batch.scheduled-tasks.index', ['group_id' => 1]));

    $response->assertStatus(200);
    $response->assertSee('田中太郎');  // name が表示される
});

test('担当者のnameが空の場合usernameが表示される', function (): void
{
    $assignedUser = User::factory()->create([
        'group_id' => 1,
        'name' => null,  // nameが空
        'username' => 'user_without_name',
    ]);

    $scheduledTask = ScheduledGroupTask::factory()->create([
        'group_id' => 1,
        'title' => 'username表示タスク',
        'auto_assign' => false,
        'assigned_user_id' => $assignedUser->id,
        'created_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('batch.scheduled-tasks.index', ['group_id' => 1]));

    $response->assertStatus(200);
    $response->assertSee('user_without_name');  // username が表示される
});

test('自動割当の場合はランダムと表示される', function (): void
{
    $scheduledTask = ScheduledGroupTask::factory()->create([
        'group_id' => 1,
        'title' => '自動割当タスク',
        'auto_assign' => true,
        'assigned_user_id' => null,  // 担当者なし
        'created_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('batch.scheduled-tasks.index', ['group_id' => 1]));

    $response->assertStatus(200);
    $response->assertSee('ランダム');  // "ランダム" が表示される
});

test('複数のスケジュールタスクの担当者が正しく表示される', function (): void
{
    $user1 = User::factory()->create([
        'group_id' => 1,
        'name' => 'ユーザー1',
        'username' => 'user1',
    ]);

    $user2 = User::factory()->create([
        'group_id' => 1,
        'name' => null,  // nameなし
        'username' => 'user2',
    ]);

    // 固定担当者（name表示）
    $task1 = ScheduledGroupTask::factory()->create([
        'group_id' => 1,
        'title' => 'タスク1',
        'auto_assign' => false,
        'assigned_user_id' => $user1->id,
        'created_by' => $this->admin->id,
    ]);

    // 固定担当者（username表示）
    $task2 = ScheduledGroupTask::factory()->create([
        'group_id' => 1,
        'title' => 'タスク2',
        'auto_assign' => false,
        'assigned_user_id' => $user2->id,
        'created_by' => $this->admin->id,
    ]);

    // 自動割当（ランダム表示）
    $task3 = ScheduledGroupTask::factory()->create([
        'group_id' => 1,
        'title' => 'タスク3',
        'auto_assign' => true,
        'assigned_user_id' => null,
        'created_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('batch.scheduled-tasks.index', ['group_id' => 1]));

    $response->assertStatus(200);
    $response->assertSee('ユーザー1');
    $response->assertSee('user2');
    $response->assertSee('ランダム');
});
