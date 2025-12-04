<?php

echo "=== delete_incomplete_previous=true のスケジュールタスク確認 ===\n\n";

$scheduledTasks = App\Models\ScheduledGroupTask::where('delete_incomplete_previous', true)
    ->where('is_active', true)
    ->get();

echo "対象スケジュールタスク数: " . $scheduledTasks->count() . "\n\n";

$totalDeleteTarget = 0;
$deleteTargetDetails = [];

foreach ($scheduledTasks as $st) {
    echo "【ID: {$st->id}】 {$st->title}\n";
    
    // 最後の成功実行を取得
    $lastExecution = App\Models\ScheduledTaskExecution::where('scheduled_task_id', $st->id)
        ->where('status', 'success')
        ->whereNotNull('created_task_id')
        ->latest('executed_at')
        ->first();
    
    if (!$lastExecution) {
        echo "  → 実行履歴なし\n\n";
        continue;
    }
    
    echo "  最終実行: {$lastExecution->executed_at}\n";
    
    // 最後に作成されたタスクのgroup_task_idを取得
    $lastTask = App\Models\Task::withTrashed()->find($lastExecution->created_task_id);
    
    if (!$lastTask || !$lastTask->group_task_id) {
        echo "  → グループタスクIDなし\n\n";
        continue;
    }
    
    $latestGroupTaskId = $lastTask->group_task_id;
    echo "  最新グループタスクID: {$latestGroupTaskId} (除外対象)\n";
    
    // 過去の実行履歴から削除対象を検索
    $olderExecutions = App\Models\ScheduledTaskExecution::where('scheduled_task_id', $st->id)
        ->where('status', 'success')
        ->whereNotNull('created_task_id')
        ->where('id', '<', $lastExecution->id)
        ->orderBy('executed_at', 'desc')
        ->limit(20)
        ->get();
    
    $taskCount = 0;
    
    foreach ($olderExecutions as $oldExec) {
        $oldTask = App\Models\Task::withTrashed()->find($oldExec->created_task_id);
        if (!$oldTask || !$oldTask->group_task_id) continue;
        
        // 最新のグループタスクIDは除外
        if ($oldTask->group_task_id === $latestGroupTaskId) continue;
        
        // 未完了・未削除のタスクを検索
        $incompleteTasks = App\Models\Task::where('group_task_id', $oldTask->group_task_id)
            ->where('is_completed', false)
            ->whereNull('deleted_at')
            ->get();
        
        if ($incompleteTasks->count() > 0) {
            echo "  📌 [削除対象] 実行日時: {$oldExec->executed_at}\n";
            echo "     グループタスクID: {$oldTask->group_task_id}\n";
            echo "     未完了タスク数: {$incompleteTasks->count()}件\n";
            echo "     タスクID: " . $incompleteTasks->pluck('id')->implode(', ') . "\n";
            
            $taskCount += $incompleteTasks->count();
            
            // 削除対象として記録
            foreach ($incompleteTasks as $task) {
                $deleteTargetDetails[] = [
                    'scheduled_task_id' => $st->id,
                    'scheduled_task_title' => $st->title,
                    'group_task_id' => $oldTask->group_task_id,
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'created_at' => $task->created_at,
                ];
            }
        }
    }
    
    echo "  削除対象合計: {$taskCount}件\n\n";
    $totalDeleteTarget += $taskCount;
}

echo "\n=== 削除対象サマリー ===\n";
echo "総削除対象タスク数: {$totalDeleteTarget}件\n\n";

if ($totalDeleteTarget > 0) {
    echo "削除対象詳細:\n";
    foreach ($deleteTargetDetails as $detail) {
        echo "  TaskID {$detail['task_id']}: {$detail['task_title']} ";
        echo "(作成: {$detail['created_at']}, GroupID: {$detail['group_task_id']})\n";
    }
}
