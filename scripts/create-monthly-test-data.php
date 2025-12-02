<?php

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "【9月・10月分テストデータ作成】\n\n";

// グループID 1のメンバー取得
$groupId = 1;
$members = \App\Models\User::where('group_id', $groupId)->get();

echo "対象グループ: {$groupId}\n";
echo "メンバー数: {$members->count()}人\n\n";

// 9月と10月のデータを作成
$months = [
    ['year' => 2024, 'month' => 9, 'label' => '9月'],
    ['year' => 2024, 'month' => 10, 'label' => '10月'],
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
        $memberTaskSummary = [];
        $groupTaskSummary = [];
        $totalTasks = 0;
        $completedTasks = 0;
        $totalReward = 0;
        
        foreach ($members as $index => $member) {
            // メンバーごとに異なる量のタスクを作成（前月比変化を演出）
            if ($month == 9) {
                $normalTaskCounts = [8, 12, 10, 15, 6, 9];
                $groupTaskCounts = [5, 8, 6, 10, 4, 6];
            } else { // 10月
                $normalTaskCounts = [10, 15, 10, 8, 7, 12];
                $groupTaskCounts = [8, 10, 7, 5, 8, 10];
            }
            
            $normalTaskCount = $normalTaskCounts[$index % 6];
            $groupTaskCount = $groupTaskCounts[$index % 6];
            
            $normalCompleted = (int)($normalTaskCount * 0.85); // 85%完了
            $groupCompleted = (int)($groupTaskCount * 0.9);    // 90%完了
            
            $normalReward = $normalCompleted * 100;
            $groupReward = $groupCompleted * 150;
            
            // 通常タスク作成
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
                }
            }
            
            // グループタスク作成
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
                }
            }
            
            // サマリーデータ作成
            $memberTaskSummary[] = [
                'user_id' => $member->id,
                'user_name' => $member->username,
                'completed_count' => $normalCompleted,
                'reward' => $normalReward,
            ];
            
            $groupTaskSummary[] = [
                'user_id' => $member->id,
                'user_name' => $member->username,
                'completed_count' => $groupCompleted,
                'reward' => $groupReward,
            ];
            
            echo "✅ {$member->username}: 通常 {$normalCompleted}/{$normalTaskCount}件, グループ {$groupCompleted}/{$groupTaskCount}件\n";
        }
        
        echo "\n";
        
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
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'completion_rate' => $completionRate,
            'total_reward' => $totalReward,
            'member_task_summary' => $memberTaskSummary,
            'group_task_summary' => $groupTaskSummary,
        ]);
        
        echo "📊 月次レポート作成完了 (ID: {$report->id})\n";
        echo "   - 総タスク: {$totalTasks}件\n";
        echo "   - 完了タスク: {$completedTasks}件\n";
        echo "   - 完了率: {$completionRate}%\n";
        echo "   - 総報酬: {$totalReward}トークン\n\n";
        
        DB::commit();
        
    } catch (\Exception $e) {
        DB::rollBack();
        echo "❌ エラー: {$e->getMessage()}\n\n";
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━\n";
echo "【トークン推定消費量算定】\n";
echo "━━━━━━━━━━━━━━━━━━━━━━\n\n";

// 9月→10月の変化を計算
$septReport = \App\Models\MonthlyReport::where('group_id', $groupId)
    ->where('report_month', '2024-09-01')
    ->first();

$octReport = \App\Models\MonthlyReport::where('group_id', $groupId)
    ->where('report_month', '2024-10-01')
    ->first();

if ($septReport && $octReport) {
    echo "【9月→10月のメンバー変化】\n";
    
    $septMembers = collect($septReport->member_task_summary);
    $octMembers = collect($octReport->member_task_summary);
    
    $septGroup = collect($septReport->group_task_summary);
    $octGroup = collect($octReport->group_task_summary);
    
    $changes = 0;
    
    foreach ($octMembers as $octMember) {
        $userId = $octMember['user_id'];
        $userName = $octMember['user_name'];
        
        $septMember = $septMembers->firstWhere('user_id', $userId);
        $septGroupMember = $septGroup->firstWhere('user_id', $userId);
        $octGroupMember = $octGroup->firstWhere('user_id', $userId);
        
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
