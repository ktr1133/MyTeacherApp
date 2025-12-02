<?php

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "【9月・10月・11月分テストデータ作成】\n\n";

// グループID 1のメンバー取得
$groupId = 1;
$members = \App\Models\User::where('group_id', $groupId)->get();

echo "対象グループ: {$groupId}\n";
echo "メンバー数: {$members->count()}人\n\n";

// 9月・10月・11月のデータを作成（2025年）
$months = [
    ['year' => 2025, 'month' => 9, 'label' => '9月'],
    ['year' => 2025, 'month' => 10, 'label' => '10月'],
    ['year' => 2025, 'month' => 11, 'label' => '11月'],
];

foreach ($months as $monthData) {
    $year = $monthData['year'];
    $month = $monthData['month'];
    $label = $monthData['label'];
    
    echo "━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "【{$label}分データ作成】\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    DB::beginTransaction();
    
    try {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        
        echo "期間: {$startDate->format('Y-m-d')} 〜 {$endDate->format('Y-m-d')}\n\n";
        
        // メンバーごとにタスクデータを作成
        $memberTaskSummary = []; // user_id => データ の連想配列
        $groupTaskSummary = [];  // user_id => データ の連想配列
        $groupTaskDetails = [];  // group_task_details用の配列
        $totalTasks = 0;
        $completedTasks = 0;
        $totalReward = 0;
        $groupTaskCompletedCount = 0;
        $groupTaskTotalReward = 0;
        
        foreach ($members as $index => $member) {
            // メンバーごとに異なる量のタスクを作成（前月比変化を演出）
            if ($month == 9) {
                $normalTaskCounts = [8, 12, 10, 15, 6, 9];
                $groupTaskCounts = [5, 8, 6, 10, 4, 6];
            } elseif ($month == 10) {
                $normalTaskCounts = [10, 15, 10, 8, 7, 12];
                $groupTaskCounts = [8, 10, 7, 5, 8, 10];
            } else { // 11月
                $normalTaskCounts = [12, 14, 11, 9, 8, 13];
                $groupTaskCounts = [9, 11, 8, 6, 9, 11];
            }
            
            $normalTaskCount = $normalTaskCounts[$index % 6];
            $groupTaskCount = $groupTaskCounts[$index % 6];
            
            $normalCompleted = (int)($normalTaskCount * 0.85); // 85%完了
            $groupCompleted = (int)($groupTaskCount * 0.9);    // 90%完了
            
            $normalReward = $normalCompleted * 100;
            $groupReward = $groupCompleted * 150;
            
            // 通常タスク作成（完了済みのもののみmember_task_summaryに記録）
            $completedNormalTasks = [];
            for ($i = 0; $i < $normalTaskCount; $i++) {
                $taskDate = $startDate->copy()->addDays(rand(0, $endDate->day - 1));
                $isCompleted = $i < $normalCompleted;
                
                $taskNum = $i + 1;
                $task = \App\Models\Task::create([
                    'user_id' => $member->id,
                    'title' => "{$label}通常タスク{$taskNum} - {$member->username}",
                    'description' => "{$member->username}の{$label}分通常タスクです。",
                    'priority' => rand(1, 5),
                    'estimated_hours' => rand(1, 8),
                    'due_date' => $taskDate->format('Y-m-d'),
                    'completed_at' => $isCompleted ? $taskDate->format('Y-m-d H:i:s') : null,
                    'reward' => 100,
                ]);
                
                $totalTasks++;
                if ($isCompleted) {
                    $completedTasks++;
                    $totalReward += 100;
                    $completedNormalTasks[] = [
                        'task_id' => $task->id,
                        'title' => $task->title,
                        'completed_at' => $task->completed_at->format('Y-m-d H:i:s'),
                    ];
                }
            }
            
            // グループタスク作成（完了済みのもののみgroup_task_summaryに記録）
            $completedGroupTasks = [];
            for ($i = 0; $i < $groupTaskCount; $i++) {
                $taskDate = $startDate->copy()->addDays(rand(0, $endDate->day - 1));
                $isCompleted = $i < $groupCompleted;
                
                // グループタスクの作成者を交代で設定
                $assignedBy = $members[($index + 1) % $members->count()]->id;
                
                $taskNum = $i + 1;
                $task = \App\Models\Task::create([
                    'user_id' => $member->id,
                    'title' => "{$label}グループタスク{$taskNum} - {$member->username}",
                    'description' => "{$member->username}への{$label}分グループタスクです。",
                    'priority' => rand(1, 5),
                    'estimated_hours' => rand(1, 8),
                    'due_date' => $taskDate->format('Y-m-d'),
                    'completed_at' => $isCompleted ? $taskDate->format('Y-m-d H:i:s') : null,
                    'reward' => 150,
                    'assigned_by_user_id' => $assignedBy,
                    'group_task_id' => \Illuminate\Support\Str::uuid(),
                    'requires_approval' => true,
                    'approved_at' => $isCompleted ? $taskDate->format('Y-m-d H:i:s') : null,
                    'approved_by_user_id' => $isCompleted ? $assignedBy : null,
                ]);
                
                $totalTasks++;
                if ($isCompleted) {
                    $completedTasks++;
                    $totalReward += 150;
                    $groupTaskCompletedCount++;
                    $groupTaskTotalReward += 150;
                    
                    $completedGroupTasks[] = [
                        'task_id' => $task->id,
                        'title' => $task->title,
                        'reward' => 150,
                        'completed_at' => $task->completed_at->format('Y-m-d H:i:s'),
                        'tags' => [],
                    ];
                    
                    $groupTaskDetails[] = [
                        'task_id' => $task->id,
                        'title' => $task->title,
                        'user_id' => $member->id,
                        'user_name' => $member->name ?: $member->username,
                        'reward' => 150,
                        'completed_at' => $task->completed_at->format('Y-m-d H:i:s'),
                        'tags' => [],
                    ];
                }
            }
            
            // member_task_summary: user_idをキーにした連想配列
            if (!empty($completedNormalTasks)) {
                $memberTaskSummary[$member->id] = [
                    'user_name' => $member->username,
                    'completed_count' => $normalCompleted,
                    'tasks' => $completedNormalTasks,
                ];
            }
            
            // group_task_summary: user_idをキーにした連想配列
            if (!empty($completedGroupTasks)) {
                $groupTaskSummary[$member->id] = [
                    'name' => $member->name ?: $member->username,
                    'completed_count' => $groupCompleted,
                    'reward' => $groupReward,
                    'tasks' => $completedGroupTasks,
                ];
            }
            
            echo "✅ {$member->username}: 通常 {$normalCompleted}/{$normalTaskCount}件, グループ {$groupCompleted}/{$groupTaskCount}件\n";
        }
        
        echo "\n";
        
        // 前月データ取得
        $previousMonth = Carbon::create($year, $month, 1)->subMonth()->format('Y-m');
        $previousReport = \App\Models\MonthlyReport::where('group_id', $groupId)
            ->where('report_month', 'like', $previousMonth . '%')
            ->first();
        
        $normalTaskCountPrevious = 0;
        $groupTaskCountPrevious = 0;
        $rewardPrevious = 0;
        
        if ($previousReport) {
            foreach ($previousReport->member_task_summary ?? [] as $summary) {
                $normalTaskCountPrevious += $summary['completed_count'] ?? 0;
            }
            $groupTaskCountPrevious = $previousReport->group_task_completed_count ?? 0;
            $rewardPrevious = $previousReport->group_task_total_reward ?? 0;
        }
        
        // 月次レポート作成
        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0;
        
        // 既存レポート削除（重複回避）
        $reportMonth = Carbon::create($year, $month, 1)->format('Y-m-d');
        \App\Models\MonthlyReport::where('group_id', $groupId)
            ->where('report_month', $reportMonth)
            ->delete();
        
        $report = \App\Models\MonthlyReport::create([
            'group_id' => $groupId,
            'report_month' => $reportMonth,
            'generated_at' => now(),
            'member_task_summary' => $memberTaskSummary,
            'group_task_completed_count' => $groupTaskCompletedCount,
            'group_task_total_reward' => $groupTaskTotalReward,
            'group_task_details' => $groupTaskDetails,
            'group_task_summary' => $groupTaskSummary,
            'normal_task_count_previous_month' => $normalTaskCountPrevious,
            'group_task_count_previous_month' => $groupTaskCountPrevious,
            'reward_previous_month' => $rewardPrevious,
        ]);
        
        echo "📊 月次レポート作成完了 (ID: {$report->id})\n";
        echo "   - 総タスク: {$totalTasks}件\n";
        echo "   - 完了タスク: {$completedTasks}件\n";
        echo "   - 完了率: {$completionRate}%\n";
        echo "   - グループタスク完了: {$groupTaskCompletedCount}件\n";
        echo "   - グループタスク報酬: {$groupTaskTotalReward}トークン\n\n";
        
        DB::commit();
        
    } catch (\Exception $e) {
        DB::rollBack();
        echo "❌ エラー: {$e->getMessage()}\n\n";
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━\n";
echo "【トークン推定消費量算定】\n";
echo "━━━━━━━━━━━━━━━━━━━━━━\n\n";

// 9月→10月の変化を計算（2025年）
$septReport = \App\Models\MonthlyReport::where('group_id', $groupId)
    ->where('report_month', '2025-09-01')
    ->first();

$octReport = \App\Models\MonthlyReport::where('group_id', $groupId)
    ->where('report_month', '2025-10-01')
    ->first();

if ($septReport && $octReport) {
    echo "【2025年9月→10月のメンバー変化】\n";
    
    $septMembers = $septReport->member_task_summary ?? [];
    $octMembers = $octReport->member_task_summary ?? [];
    
    $septGroup = $septReport->group_task_summary ?? [];
    $octGroup = $octReport->group_task_summary ?? [];
    
    $changes = 0;
    
    foreach ($octMembers as $userId => $octMember) {
        $userName = $octMember['user_name'];
        
        $septMember = $septMembers[$userId] ?? null;
        $septGroupMember = $septGroup[$userId] ?? null;
        $octGroupMember = $octGroup[$userId] ?? null;
        
        $septTotal = ($septMember['completed_count'] ?? 0) + ($septGroupMember['completed_count'] ?? 0);
        $octTotal = ($octMember['completed_count'] ?? 0) + ($octGroupMember['completed_count'] ?? 0);
        
        if ($septTotal > 0) {
            $changePercentage = round((($octTotal - $septTotal) / $septTotal) * 100);
            $icon = $changePercentage >= 30 ? '📈' : ($changePercentage <= -30 ? '📉' : '➡️');
            
            echo "{$icon} {$userName}: {$septTotal}件 → {$octTotal}件 (" . sprintf('%+d', $changePercentage) . "%)\n";
            
            if (abs($changePercentage) >= 30) {
                $changes++;
            }
        }
    }
    
    echo "\n30%以上の変化: {$changes}名\n\n";
}

// トークン推定
echo "【AIコメント生成時のトークン推定】\n\n";

$basePromptTokens = 150; // システムプロンプトベース
$memberChangeTokens = 60; // 1メンバーあたりの変化説明
$userPromptTokens = 100;  // ユーザープロンプト
$responseTokens = 300;    // AIレスポンス（max_tokens設定値）

$totalInputTokens = $basePromptTokens + ($changes * $memberChangeTokens) + $userPromptTokens;
$totalTokens = $totalInputTokens + $responseTokens;

echo "入力トークン推定:\n";
echo "  - ベースプロンプト: {$basePromptTokens}トークン\n";
echo "  - メンバー変化情報: {$changes}名 × {$memberChangeTokens} = " . ($changes * $memberChangeTokens) . "トークン\n";
echo "  - ユーザープロンプト: {$userPromptTokens}トークン\n";
echo "  - 合計入力: {$totalInputTokens}トークン\n\n";

echo "出力トークン推定:\n";
echo "  - AIレスポンス: {$responseTokens}トークン（max_tokens設定）\n\n";

echo "総トークン推定: {$totalTokens}トークン\n\n";

// gpt-4o-miniの料金（2024年12月時点）
$inputCostPer1M = 0.150;  // $0.150 per 1M input tokens
$outputCostPer1M = 0.600; // $0.600 per 1M output tokens

$inputCost = ($totalInputTokens / 1000000) * $inputCostPer1M;
$outputCost = ($responseTokens / 1000000) * $outputCostPer1M;
$totalCost = $inputCost + $outputCost;

echo "【料金推定（gpt-4o-mini）】\n";
echo "  - 入力コスト: \$" . number_format($inputCost, 6) . "\n";
echo "  - 出力コスト: \$" . number_format($outputCost, 6) . "\n";
echo "  - 合計: \$" . number_format($totalCost, 6) . " (約" . number_format($totalCost * 150, 4) . "円)\n\n";

echo "✅ テストデータ作成完了！\n";
