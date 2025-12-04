#!/usr/bin/env php
<?php

/**
 * 本番環境: 削除すべき未完了タスクを確認
 * 
 * delete_incomplete_previous=true のスケジュールタスクで、
 * 最新実行以外の未完了タスクを検出します
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ScheduledGroupTask;
use App\Models\ScheduledTaskExecution;
use App\Models\Task;

echo "=== 本番環境: 未完了タスク確認 ===" . PHP_EOL . PHP_EOL;

// 前回未完了タスク削除設定があるスケジュールタスクを取得
$scheduledTasks = ScheduledGroupTask::where('delete_incomplete_previous', true)
    ->where('is_active', true)
    ->get();

echo "delete_incomplete_previous=true のスケジュールタスク: {$scheduledTasks->count()}件" . PHP_EOL . PHP_EOL;

$targetTasks = [];
$deletionInfo = [];

foreach ($scheduledTasks as $st) {
    echo "【スケジュールタスク ID: {$st->id}】" . PHP_EOL;
    echo "タイトル: {$st->title}" . PHP_EOL;
    
    // 最後の成功実行を取得
    $lastExecution = ScheduledTaskExecution::where('scheduled_task_id', $st->id)
        ->where('status', 'success')
        ->whereNotNull('created_task_id')
        ->latest('executed_at')
        ->first();
    
    if (!$lastExecution) {
        echo "  → 実行履歴なし" . PHP_EOL . PHP_EOL;
        continue;
    }
    
    echo "最終実行: {$lastExecution->executed_at}" . PHP_EOL;
    
    $lastTask = Task::withTrashed()->find($lastExecution->created_task_id);
    
    if (!$lastTask || !$lastTask->group_task_id) {
        echo "  → グループタスクIDなし" . PHP_EOL . PHP_EOL;
        continue;
    }
    
    $latestGroupTaskId = $lastTask->group_task_id;
    echo "最新グループタスクID: {$latestGroupTaskId}" . PHP_EOL;
    
    // 過去の実行履歴を確認（最新を除く）
    $olderExecutions = ScheduledTaskExecution::where('scheduled_task_id', $st->id)
        ->where('status', 'success')
        ->whereNotNull('created_task_id')
        ->where('id', '<', $lastExecution->id)
        ->orderBy('executed_at', 'desc')
        ->limit(20)
        ->get();
    
    $foundOldTasks = false;
    
    foreach ($olderExecutions as $oldExec) {
        $oldTask = Task::withTrashed()->find($oldExec->created_task_id);
        if (!$oldTask || !$oldTask->group_task_id) continue;
        
        // 最新のグループタスクIDと同じ場合はスキップ
        if ($oldTask->group_task_id === $latestGroupTaskId) continue;
        
        // 未完了・未削除のタスクを取得
        $oldGroupTasks = Task::where('group_task_id', $oldTask->group_task_id)
            ->where('is_completed', false)
            ->whereNull('deleted_at')
            ->get();
        
        if ($oldGroupTasks->count() > 0) {
            $foundOldTasks = true;
            echo PHP_EOL;
            echo "  ⚠️ 【削除対象】" . PHP_EOL;
            echo "  実行日時: {$oldExec->executed_at}" . PHP_EOL;
            echo "  グループタスクID: {$oldTask->group_task_id}" . PHP_EOL;
            echo "  未完了タスク数: {$oldGroupTasks->count()}件" . PHP_EOL;
            echo "  タスクID: " . $oldGroupTasks->pluck('id')->implode(', ') . PHP_EOL;
            
            foreach ($oldGroupTasks as $task) {
                $targetTasks[] = $task->id;
                $deletionInfo[] = [
                    'task_id' => $task->id,
                    'scheduled_task_id' => $st->id,
                    'scheduled_task_title' => $st->title,
                    'group_task_id' => $oldTask->group_task_id,
                    'executed_at' => $oldExec->executed_at,
                    'title' => $task->title,
                    'user_id' => $task->user_id,
                ];
            }
        }
    }
    
    if (!$foundOldTasks) {
        echo "  → 削除対象なし（すべて最新または完了済み）" . PHP_EOL;
    }
    
    echo PHP_EOL . str_repeat('-', 80) . PHP_EOL . PHP_EOL;
}

echo "=== サマリー ===" . PHP_EOL;
echo "削除対象タスク総数: " . count($targetTasks) . "件" . PHP_EOL . PHP_EOL;

if (count($targetTasks) > 0) {
    echo "【削除対象タスク一覧】" . PHP_EOL;
    foreach ($deletionInfo as $info) {
        echo sprintf(
            "- タスクID: %d | スケジュール: %s | 実行日: %s | ユーザーID: %d%s",
            $info['task_id'],
            $info['scheduled_task_title'],
            $info['executed_at'],
            $info['user_id'],
            PHP_EOL
        );
    }
    
    echo PHP_EOL . "【削除SQL】" . PHP_EOL;
    echo "-- 以下のSQLで削除できます（deleted_atに現在時刻を設定）" . PHP_EOL;
    echo "UPDATE tasks SET deleted_at = NOW() WHERE id IN (" . implode(', ', $targetTasks) . ");" . PHP_EOL;
} else {
    echo "✅ 削除すべき未完了タスクは見つかりませんでした" . PHP_EOL;
}

echo PHP_EOL . "=== 完了 ===" . PHP_EOL;
