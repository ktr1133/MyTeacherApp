#!/usr/bin/env php
<?php

// testuserのグループタスクデータを確認するスクリプト

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$testUser = DB::table('users')->where('username', 'testuser')->first();

if (!$testUser) {
    echo "❌ testuser not found\n";
    exit(1);
}

echo "✅ testuser ID: {$testUser->id}\n";
echo "✅ Group ID: " . ($testUser->group_id ?? 'null') . "\n";

if (!$testUser->group_id) {
    echo "❌ testuser has no group\n";
    exit(1);
}

// グループメンバー確認
$members = DB::table('users')
    ->where('group_id', $testUser->group_id)
    ->get(['id', 'username']);

echo "\n=== Group Members ===\n";
foreach ($members as $member) {
    echo "  - {$member->username} (ID: {$member->id})\n";
}

// グループタスク数確認
$groupTaskCount = DB::table('tasks')
    ->whereNotNull('group_task_id')
    ->whereIn('user_id', $members->pluck('id'))
    ->count();

echo "\n=== Group Tasks ===\n";
echo "Total group tasks: {$groupTaskCount}\n";

// testuserのグループタスク詳細
$testuserGroupTasks = DB::table('tasks')
    ->whereNotNull('group_task_id')
    ->where('user_id', $testUser->id)
    ->count();

echo "testuser's group tasks: {$testuserGroupTasks}\n";

// 承認済みタスク確認
$approvedTasks = DB::table('tasks')
    ->whereNotNull('group_task_id')
    ->whereNotNull('approved_at')
    ->where('user_id', $testUser->id)
    ->count();

echo "Approved group tasks: {$approvedTasks}\n";

// 完了済みタスク確認
$completedTasks = DB::table('tasks')
    ->whereNotNull('group_task_id')
    ->where('is_completed', true)
    ->where('user_id', $testUser->id)
    ->count();

echo "Completed group tasks: {$completedTasks}\n";

// サンプルデータ表示（最初の1件）
$sample = DB::table('tasks')
    ->whereNotNull('group_task_id')
    ->where('user_id', $testUser->id)
    ->orderBy('created_at', 'desc')
    ->first();

if ($sample) {
    echo "\n=== Sample Group Task ===\n";
    echo "Title: {$sample->title}\n";
    echo "Group Task ID: {$sample->group_task_id}\n";
    echo "User ID: {$sample->user_id}\n";
    echo "Assigned By: " . ($sample->assigned_by_user_id ?? 'null') . "\n";
    echo "Is Completed: " . ($sample->is_completed ? 'true' : 'false') . "\n";
    echo "Completed At: " . ($sample->completed_at ?? 'null') . "\n";
    echo "Approved At: " . ($sample->approved_at ?? 'null') . "\n";
    echo "Approved By: " . ($sample->approved_by_user_id ?? 'null') . "\n";
    echo "Reward: " . ($sample->reward ?? '0') . "\n";
    echo "Requires Approval: " . ($sample->requires_approval ? 'true' : 'false') . "\n";
}

// 2025年9-11月のデータ確認
echo "\n=== Date Range Check (2025-09 to 2025-11) ===\n";
$monthlyTasks = DB::table('tasks')
    ->whereNotNull('group_task_id')
    ->where('user_id', $testUser->id)
    ->whereBetween('completed_at', ['2025-09-01', '2025-11-30'])
    ->count();

echo "Group tasks in Sep-Nov 2025: {$monthlyTasks}\n";
