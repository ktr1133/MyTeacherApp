<?php

namespace App\Services\Report;

use App\Models\Group;
use App\Models\MonthlyReport;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Report\MonthlyReportRepositoryInterface;
use App\Services\AI\OpenAIServiceInterface;
use App\Services\Subscription\SubscriptionServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * 月次レポートサービス実装
 * 
 * 月次レポート生成・取得・データ整形のビジネスロジック
 */
class MonthlyReportService implements MonthlyReportServiceInterface
{
    /**
     * コンストラクタ
     * 
     * @param MonthlyReportRepositoryInterface $repository レポートリポジトリ
     * @param SubscriptionServiceInterface $subscriptionService サブスクリプションサービス
     * @param OpenAIServiceInterface $openAIService OpenAIサービス
     */
    public function __construct(
        protected MonthlyReportRepositoryInterface $repository,
        protected SubscriptionServiceInterface $subscriptionService,
        protected OpenAIServiceInterface $openAIService
    ) {}
    
    /**
     * グループの月次レポートを生成
     * 
     * @param Group $group 対象グループ
     * @param string $yearMonth 対象年月（YYYY-MM形式）
     * @return MonthlyReport 生成されたレポート
     * @throws \RuntimeException レポート生成失敗時
     */
    public function generateMonthlyReport(Group $group, string $yearMonth): MonthlyReport
    {
        try {
            DB::beginTransaction();
            
            // 既存レポート確認
            $existingReport = $this->repository->findByGroupAndMonth($group->id, $yearMonth);
            
            // 対象月の範囲を計算
            $startDate = Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
            
            // メンバー別通常タスク集計
            $memberTaskSummary = $this->calculateMemberTaskSummary($group, $startDate, $endDate);
            
            // グループタスク集計
            $groupTaskSummary = $this->calculateGroupTaskSummary($group, $startDate, $endDate);
            
            // グループタスクのユーザー別集計（group_task_summary用）
            $groupTaskSummaryByUser = $this->calculateGroupTaskSummaryByUser($group, $startDate, $endDate);
            
            // 前月データ取得
            $previousMonthData = $this->getPreviousMonthData($group, $yearMonth);
            
            // レポートデータ作成
            $reportData = [
                'group_id' => $group->id,
                'report_month' => $startDate->format('Y-m-d'),
                'generated_at' => now(),
                'member_task_summary' => $memberTaskSummary,
                'group_task_completed_count' => $groupTaskSummary['completed_count'],
                'group_task_total_reward' => $groupTaskSummary['total_reward'],
                'group_task_details' => $groupTaskSummary['details'],
                'group_task_summary' => $groupTaskSummaryByUser,
                'normal_task_count_previous_month' => $previousMonthData['normal_task_count'],
                'group_task_count_previous_month' => $previousMonthData['group_task_count'],
                'reward_previous_month' => $previousMonthData['reward'],
            ];
            
            // AIコメント生成（エラーがあっても続行）
            try {
                $aiComment = $this->generateAIComment($group, $reportData);
                $reportData['ai_comment'] = $aiComment['comment'];
                $reportData['ai_comment_tokens_used'] = $aiComment['tokens_used'];
            } catch (\Exception $e) {
                Log::warning('AI comment generation failed, continuing without comment', [
                    'group_id' => $group->id,
                    'year_month' => $yearMonth,
                    'error' => $e->getMessage(),
                ]);
                $reportData['ai_comment'] = null;
                $reportData['ai_comment_tokens_used'] = 0;
            }
            
            // レポート保存または更新
            if ($existingReport) {
                $report = $this->repository->update($existingReport, $reportData);
            } else {
                $report = $this->repository->create($reportData);
            }
            
            DB::commit();
            
            Log::info('Monthly report generated', [
                'group_id' => $group->id,
                'year_month' => $yearMonth,
                'report_id' => $report->id,
            ]);
            
            return $report;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to generate monthly report', [
                'group_id' => $group->id,
                'year_month' => $yearMonth,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw new \RuntimeException('月次レポートの生成に失敗しました: ' . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * メンバー別通常タスク集計を計算
     * 
     * @param Group $group 対象グループ
     * @param Carbon $startDate 開始日
     * @param Carbon $endDate 終了日
     * @return array メンバー別集計データ
     */
    protected function calculateMemberTaskSummary(Group $group, Carbon $startDate, Carbon $endDate): array
    {
        $summary = [];
        
        foreach ($group->users as $user) {
            // 通常タスク（グループタスクでない）の完了タスクを取得
            $completedTasks = Task::where('user_id', $user->id)
                ->whereNull('group_task_id')
                ->where('is_completed', true)
                ->whereNotNull('completed_at')
                ->whereBetween('completed_at', [$startDate, $endDate])
                ->orderBy('completed_at', 'desc')
                ->get(['id', 'title', 'completed_at']);
            
            $summary[$user->id] = [
                'user_name' => $user->name,
                'username' => $user->username,
                'completed_count' => $completedTasks->count(),
                'tasks' => $completedTasks->map(fn($task) => [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'completed_at' => $task->completed_at->format('Y-m-d H:i:s'),
                ])->toArray(),
            ];
        }
        
        return $summary;
    }
    
    /**
     * グループタスク集計を計算
     * 
     * @param Group $group 対象グループ
     * @param Carbon $startDate 開始日
     * @param Carbon $endDate 終了日
     * @return array グループタスク集計データ
     */
    protected function calculateGroupTaskSummary(Group $group, Carbon $startDate, Carbon $endDate): array
    {
        // グループに所属するユーザーのグループタスク完了を取得
        $userIds = $group->users->pluck('id')->toArray();
        
        $completedGroupTasks = Task::whereIn('user_id', $userIds)
            ->whereNotNull('group_task_id')
            ->where('is_completed', true)
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->with(['user:id,name', 'tags:id,name'])
            ->orderBy('completed_at', 'desc')
            ->get(['id', 'title', 'user_id', 'reward', 'completed_at']);
        
        $totalReward = $completedGroupTasks->sum('reward');
        
        $details = $completedGroupTasks->map(fn($task) => [
            'task_id' => $task->id,
            'title' => $task->title,
            'user_id' => $task->user_id,
            'user_name' => $task->user->name ?? '不明',
            'reward' => $task->reward,
            'completed_at' => $task->completed_at->format('Y-m-d H:i:s'),
            'tags' => $task->tags->pluck('name')->toArray(),
        ])->toArray();
        
        return [
            'completed_count' => $completedGroupTasks->count(),
            'total_reward' => $totalReward,
            'details' => $details,
        ];
    }
    
    /**
     * グループタスクをユーザー別に集計（group_task_summary用）
     * 
     * @param Group $group 対象グループ
     * @param Carbon $startDate 開始日
     * @param Carbon $endDate 終了日
     * @return array ユーザー別グループタスク集計
     */
    protected function calculateGroupTaskSummaryByUser(Group $group, Carbon $startDate, Carbon $endDate): array
    {
        $userIds = $group->users->pluck('id')->toArray();
        
        $completedGroupTasks = Task::whereIn('user_id', $userIds)
            ->whereNotNull('group_task_id')
            ->where('is_completed', true)
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->with(['user:id,name', 'tags:id,name'])
            ->orderBy('completed_at', 'desc')
            ->get(['id', 'title', 'user_id', 'reward', 'completed_at']);
        
        $summary = [];
        
        foreach ($group->users as $user) {
            $userTasks = $completedGroupTasks->where('user_id', $user->id);
            
            if ($userTasks->isEmpty()) {
                continue;
            }
            
            $summary[$user->id] = [
                'user_name' => $user->name,
                'username' => $user->username,
                'completed_count' => $userTasks->count(),
                'reward' => $userTasks->sum('reward'),
                'tasks' => $userTasks->map(fn($task) => [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'reward' => $task->reward,
                    'completed_at' => $task->completed_at->format('Y-m-d H:i:s'),
                    'tags' => $task->tags->pluck('name')->toArray(),
                ])->values()->toArray(),
            ];
        }
        
        return $summary;
    }
    
    /**
     * 前月データを取得
     * 
     * @param Group $group 対象グループ
     * @param string $yearMonth 基準年月（YYYY-MM形式）
     * @return array 前月データ
     */
    protected function getPreviousMonthData(Group $group, string $yearMonth): array
    {
        $previousMonth = Carbon::createFromFormat('Y-m', $yearMonth)
            ->subMonth()
            ->format('Y-m');
        
        $previousReport = $this->repository->findByGroupAndMonth($group->id, $previousMonth);
        
        if (!$previousReport) {
            return [
                'normal_task_count' => 0,
                'group_task_count' => 0,
                'reward' => 0,
            ];
        }
        
        // 通常タスク合計を計算
        $normalTaskCount = 0;
        foreach ($previousReport->member_task_summary ?? [] as $summary) {
            $normalTaskCount += $summary['completed_count'] ?? 0;
        }
        
        return [
            'normal_task_count' => $normalTaskCount,
            'group_task_count' => $previousReport->group_task_completed_count,
            'reward' => $previousReport->group_task_total_reward,
        ];
    }
    
    /**
     * グループの月次レポート一覧を取得
     * 
     * @param Group $group 対象グループ
     * @param int|null $limit 取得件数制限
     * @return Collection<MonthlyReport> レポート一覧
     */
    public function getReportsForGroup(Group $group, ?int $limit = null): Collection
    {
        return $this->repository->getByGroup($group->id, $limit);
    }
    
    /**
     * 月次レポートを取得（サブスクリプション権限チェック含む）
     * 
     * @param Group $group 対象グループ
     * @param string $yearMonth 対象年月（YYYY-MM形式）
     * @return MonthlyReport|null レポート（権限がない場合null）
     */
    public function getMonthlyReport(Group $group, string $yearMonth): ?MonthlyReport
    {
        // アクセス権限チェック
        if (!$this->canAccessReport($group, $yearMonth)) {
            return null;
        }
        
        return $this->repository->findByGroupAndMonth($group->id, $yearMonth);
    }
    
    /**
     * グループが指定年月のレポートにアクセス可能か判定
     * 
     * @param Group $group 対象グループ
     * @param string $yearMonth 対象年月（YYYY-MM形式）
     * @return bool アクセス可能な場合true
     */
    public function canAccessReport(Group $group, string $yearMonth): bool
    {
        // サブスク加入者は全期間アクセス可能
        // subscription_activeではなくCashierのsubscribed()を使用（有効期限内かチェック）
        if ($group->subscribed('default')) {
            return true;
        }
        
        // 無料ユーザーは初月（グループ作成後1ヶ月）のみアクセス可能
        $groupCreatedAt = Carbon::parse($group->created_at);
        $firstMonthEnd = $groupCreatedAt->copy()->addMonth()->endOfMonth();
        $targetMonth = Carbon::createFromFormat('Y-m', $yearMonth);
        
        // 対象月が初月範囲内かチェック
        return $targetMonth->lte($firstMonthEnd);
    }
    
    /**
     * レポートデータを表示用に整形
     * 
     * @param MonthlyReport $report レポート
     * @return array 整形されたデータ
     */
    public function formatReportForDisplay(MonthlyReport $report): array
    {
        $memberTaskSummary = $report->member_task_summary ?? [];
        $groupTaskDetails = $report->group_task_details ?? [];
        $groupTaskSummary = $report->group_task_summary ?? [];
        
        // 既存データにusernameが含まれていない場合は追加
        $group = $report->group;
        foreach ($memberTaskSummary as $userId => &$summary) {
            if (!isset($summary['username'])) {
                $user = $group->users()->find($userId);
                if ($user) {
                    $summary['user_name'] = $user->name;
                    $summary['username'] = $user->username;
                }
            }
        }
        unset($summary);
        
        // group_task_summaryにもusernameを追加
        foreach ($groupTaskSummary as $userId => &$summary) {
            if (!isset($summary['username'])) {
                $user = $group->users()->find($userId);
                if ($user) {
                    $summary['user_name'] = $user->name;
                    $summary['username'] = $user->username;
                }
            }
        }
        unset($summary);
        
        // 通常タスク合計を計算
        $totalNormalTasks = 0;
        foreach ($memberTaskSummary as $summary) {
            $totalNormalTasks += $summary['completed_count'] ?? 0;
        }
        
        // 前月比を計算
        $normalTaskChange = 0;
        $groupTaskChange = 0;
        $rewardChange = 0;
        
        if ($report->normal_task_count_previous_month > 0) {
            $normalTaskChange = (($totalNormalTasks - $report->normal_task_count_previous_month) 
                / $report->normal_task_count_previous_month) * 100;
        }
        
        if ($report->group_task_count_previous_month > 0) {
            $groupTaskChange = (($report->group_task_completed_count - $report->group_task_count_previous_month) 
                / $report->group_task_count_previous_month) * 100;
        }
        
        if ($report->reward_previous_month > 0) {
            $rewardChange = (($report->group_task_total_reward - $report->reward_previous_month) 
                / $report->reward_previous_month) * 100;
        }
        
        return [
            'report_id' => $report->id,
            'report_month' => Carbon::parse($report->report_month)->format('Y年m月'),
            'generated_at' => $report->generated_at ? $report->generated_at->format('Y-m-d H:i') : null,
            'ai_comment' => $report->ai_comment,
            'summary' => [
                'normal_tasks' => [
                    'count' => $totalNormalTasks,
                    'change_percentage' => round($normalTaskChange, 1),
                ],
                'group_tasks' => [
                    'count' => $report->group_task_completed_count,
                    'change_percentage' => round($groupTaskChange, 1),
                ],
                'rewards' => [
                    'total' => $report->group_task_total_reward,
                    'change_percentage' => round($rewardChange, 1),
                ],
            ],
            'member_details' => $memberTaskSummary,
            'group_task_details' => $groupTaskDetails,
            'group_task_summary' => $groupTaskSummary,
        ];
    }
    
    /**
     * 全グループの月次レポートを一括生成
     * 
     * @param string|null $yearMonth 対象年月（YYYY-MM形式、省略時は先月）
     * @return array ['success' => int, 'failed' => int, 'errors' => array]
     */
    public function generateReportsForAllGroups(?string $yearMonth = null): array
    {
        $targetMonth = $yearMonth ?? Carbon::now()->subMonth()->format('Y-m');
        
        $groups = $this->repository->getAllGroups();
        $successCount = 0;
        $failedCount = 0;
        $errors = [];
        
        foreach ($groups as $group) {
            try {
                $this->generateMonthlyReport($group, $targetMonth);
                $successCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = [
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'error' => $e->getMessage(),
                ];
                
                Log::error('Failed to generate report for group', [
                    'group_id' => $group->id,
                    'year_month' => $targetMonth,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        Log::info('Batch report generation completed', [
            'target_month' => $targetMonth,
            'total_groups' => $groups->count(),
            'success' => $successCount,
            'failed' => $failedCount,
        ]);
        
        return [
            'success' => $successCount,
            'failed' => $failedCount,
            'errors' => $errors,
        ];
    }
    
    /**
     * 古いレポートを削除（1年以上前）
     * 
     * @return int 削除されたレポート数
     */
    public function cleanupOldReports(): int
    {
        $oneYearAgo = Carbon::now()->subYear();
        
        $deletedCount = $this->repository->deleteOlderThan($oneYearAgo);
        
        Log::info('Old reports cleaned up', [
            'deleted_count' => $deletedCount,
            'older_than' => $oneYearAgo->format('Y-m-d'),
        ]);
        
        return $deletedCount;
    }
    
    /**
     * @inheritDoc
     */
    public function getAvailableMonths(Group $group, int $limit = 12): array
    {
        $months = [];
        $now = now();
        $groupCreatedAt = Carbon::parse($group->created_at);
        
        // 既存レポートの年月リストを取得
        $existingReports = $this->repository->getByGroup($group->id, $limit);
        $existingYearMonths = $existingReports->pluck('report_month')
            ->map(fn($date) => $date->format('Y-m'))
            ->toArray();
        
        // 過去N ヶ月分のリストを生成（既存レポートがある月は作成日に関係なく含める）
        for ($i = 0; $i < $limit; $i++) {
            $targetMonth = $now->copy()->subMonths($i);
            $yearMonth = $targetMonth->format('Y-m');
            
            // 既存レポートがある場合は常に含める、ない場合はグループ作成日以降のみ
            $hasReport = in_array($yearMonth, $existingYearMonths);
            if (!$hasReport && $targetMonth->lt($groupCreatedAt->startOfMonth())) {
                continue;
            }
            
            $months[] = [
                'year_month' => $yearMonth,
                'year' => $targetMonth->format('Y'),
                'month' => $targetMonth->format('m'),
                'label' => $targetMonth->format('Y年n月'),
                'has_report' => $hasReport,
                'is_accessible' => $this->canAccessReport($group, $yearMonth),
            ];
        }
        
        return $months;
    }
    
    /**
     * @inheritDoc
     */
    public function getTrendData(Group $group, string $yearMonth, int $months = 6): array
    {
        Log::debug('getTrendData called', [
            'group_id' => $group->id,
            'yearMonth' => $yearMonth,
            'months' => $months,
        ]);
        
        $baseDate = Carbon::createFromFormat('Y-m', $yearMonth);
        $labels = [];
        $memberData = [];
        
        // 基準月から過去N-1ヶ月（合計Nヶ月）のレポートを取得
        for ($i = $months - 1; $i >= 0; $i--) {
            $targetMonth = $baseDate->copy()->subMonths($i);
            $targetYearMonth = $targetMonth->format('Y-m');
            $labels[] = $targetMonth->format('y/m');  // n月 → y/m に変更（11月 → 25/11）
            
            $report = $this->repository->findByGroupAndMonth($group->id, $targetYearMonth);
            
            Log::debug('Fetching report for month', [
                'target' => $targetYearMonth,
                'found' => $report !== null,
                'member_count' => $report ? count($report->member_task_summary ?? []) : 0,
                'group_task_count' => $report ? count($report->group_task_summary ?? []) : 0,
            ]);
            
            if ($report && $report->member_task_summary) {
                foreach ($report->member_task_summary as $userId => $summary) {
                    if (!isset($memberData[$userId])) {
                        // ユーザー情報を取得（表示名 or username）
                        $user = \App\Models\User::find($userId);
                        $displayName = $user ? ($user->name ?: $user->username) : 'Unknown';
                        
                        $memberData[$userId] = [
                            'name' => $displayName,
                            'normal_tasks' => array_fill(0, $months, 0),
                            'group_tasks' => array_fill(0, $months, 0),
                            'rewards' => array_fill(0, $months, 0),
                        ];
                    }
                    
                    $index = $months - 1 - $i;
                    $memberData[$userId]['normal_tasks'][$index] = $summary['completed_count'] ?? 0;
                }
                
                // グループタスク集計（group_task_summaryから）
                if ($report->group_task_summary) {
                    foreach ($report->group_task_summary as $userId => $summary) {
                        if (!isset($memberData[$userId])) {
                            // ユーザー情報を取得（表示名 or username）
                            $user = \App\Models\User::find($userId);
                            $displayName = $user ? ($user->name ?: $user->username) : 'Unknown';
                            
                            $memberData[$userId] = [
                                'name' => $displayName,
                                'normal_tasks' => array_fill(0, $months, 0),
                                'group_tasks' => array_fill(0, $months, 0),
                                'rewards' => array_fill(0, $months, 0),
                            ];
                        }
                        
                        $index = $months - 1 - $i;
                        $memberData[$userId]['group_tasks'][$index] = $summary['completed_count'] ?? 0;
                        $memberData[$userId]['rewards'][$index] += $summary['reward'] ?? 0;
                    }
                }
            }
        }
        
        // Chart.js用のデータセット形式に変換（通常タスク、グループタスク、合計タスク、報酬）
        $normalDatasets = [];
        $groupDatasets = [];
        $totalDatasets = []; // 合計タスク用
        $rewardDatasets = []; // 報酬用
        $colors = [
            ['rgb(59, 130, 246)', 'rgba(59, 130, 246, 0.5)'],   // blue
            ['rgb(16, 185, 129)', 'rgba(16, 185, 129, 0.5)'],   // green
            ['rgb(251, 146, 60)', 'rgba(251, 146, 60, 0.5)'],   // orange
            ['rgb(168, 85, 247)', 'rgba(168, 85, 247, 0.5)'],   // purple
            ['rgb(236, 72, 153)', 'rgba(236, 72, 153, 0.5)'],   // pink
            ['rgb(250, 204, 21)', 'rgba(250, 204, 21, 0.5)'],   // yellow
            ['rgb(14, 165, 233)', 'rgba(14, 165, 233, 0.5)'],   // sky
            ['rgb(249, 115, 22)', 'rgba(249, 115, 22, 0.5)'],   // orange-600
            ['rgb(139, 92, 246)', 'rgba(139, 92, 246, 0.5)'],   // violet
            ['rgb(236, 72, 153)', 'rgba(236, 72, 153, 0.5)'],   // fuchsia
        ];
        
        $colorIndex = 0;
        foreach ($memberData as $userId => $data) {
            $color = $colors[$colorIndex % count($colors)];
            
            // 通常タスク
            $normalDatasets[] = [
                'label' => $data['name'],
                'data' => $data['normal_tasks'],
                'backgroundColor' => $color[1],
                'borderColor' => $color[0],
                'borderWidth' => 1,
            ];
            
            // グループタスク
            $groupDatasets[] = [
                'label' => $data['name'],
                'data' => $data['group_tasks'],
                'backgroundColor' => $color[1],
                'borderColor' => $color[0],
                'borderWidth' => 1,
            ];
            
            // 合計タスク（通常 + グループ）
            $totalTasks = array_map(function($normal, $group) {
                return $normal + $group;
            }, $data['normal_tasks'], $data['group_tasks']);
            
            $totalDatasets[] = [
                'label' => $data['name'],
                'data' => $totalTasks,
                'backgroundColor' => $color[1],
                'borderColor' => $color[0],
                'borderWidth' => 2,
                'tension' => 0.3, // 滑らかな曲線
            ];
            
            // 報酬（グループタスクのみ）
            $rewardDatasets[] = [
                'label' => $data['name'],
                'data' => $data['rewards'],
                'backgroundColor' => $color[1],
                'borderColor' => $color[0],
                'borderWidth' => 2,
                'tension' => 0.3, // 滑らかな曲線
            ];
            
            $colorIndex++;
        }
        
        Log::debug('getTrendData result', [
            'labels' => $labels,
            'normal_dataset_count' => count($normalDatasets),
            'group_dataset_count' => count($groupDatasets),
            'total_dataset_count' => count($totalDatasets),
            'reward_dataset_count' => count($rewardDatasets),
            'member_count' => count($memberData),
            'member_names' => array_map(fn($data) => $data['name'], $memberData),
        ]);
        
        return [
            'labels' => $labels,
            'normal' => [
                'labels' => $labels,
                'datasets' => $normalDatasets,
            ],
            'group' => [
                'labels' => $labels,
                'datasets' => $groupDatasets,
            ],
            'total' => [
                'labels' => $labels,
                'datasets' => $totalDatasets,
            ],
            'reward' => [
                'labels' => $labels,
                'datasets' => $rewardDatasets,
            ],
            'members' => array_map(fn($data) => $data['name'], $memberData),
        ];
    }
    
    /**
     * AIコメント生成
     *
     * @param Group $group 対象グループ
     * @param array $reportData レポートデータ
     * @return array ['comment' => string, 'tokens_used' => int]
     * @throws \RuntimeException AI生成失敗時
     */
    protected function generateAIComment(Group $group, array $reportData): array
    {
        // グループの教師アバター取得
        $avatar = $group->master->teacher_avatar ?? null;
        
        // アバター性格情報の抽出
        $personality = null;
        if ($avatar) {
            $personality = [
                'tone' => $avatar->tone ?? 'friendly',
                'enthusiasm' => $avatar->enthusiasm ?? 'moderate',
                'formality' => $avatar->formality ?? 'neutral',
                'humor' => $avatar->humor ?? 'moderate',
            ];
        }
        
        // 前月レポート取得
        $reportMonth = Carbon::createFromFormat('Y-m-d', $reportData['report_month'] ?? now()->format('Y-m-d'));
        $previousMonth = $reportMonth->copy()->subMonth();
        $previousReport = $this->repository->findByGroupAndMonth($group->id, $previousMonth->format('Y-m'));
        
        // 著しい変化があったメンバーの情報を計算
        $memberChanges = [];
        if ($previousReport) {
            $memberChanges = $this->calculateMemberChanges($reportData, $previousReport);
        }
        
        // グループメンバーのテーマを取得（最初のメンバーのテーマを代表として使用）
        $userTheme = 'child'; // デフォルトは子ども向け
        if ($group->users()->exists()) {
            $firstUser = $group->users()->first();
            $userTheme = $firstUser->theme ?? 'child';
        }
        
        // OpenAIサービスを使ってコメント生成（変化情報とテーマを含める）
        $result = $this->openAIService->generateMonthlyReportComment($reportData, $personality, $memberChanges, $userTheme);
        
        return [
            'comment' => $result['comment'],
            'tokens_used' => $result['usage']['total_tokens'] ?? 0,
        ];
    }
    
    /**
     * メンバー別の前月比変化率を計算
     * 
     * @param array $currentReportData 当月レポートデータ
     * @param MonthlyReport $previousReport 前月レポート
     * @return array 著しい変化があったメンバーの情報
     */
    protected function calculateMemberChanges(array $currentReportData, MonthlyReport $previousReport): array
    {
        $changes = [];
        $threshold = 30; // 30%以上の変化を「著しい変化」とする
        
        $currentMemberSummary = $currentReportData['member_task_summary'] ?? [];
        $currentGroupSummary = $currentReportData['group_task_summary'] ?? [];
        $previousMemberSummary = $previousReport->member_task_summary ?? [];
        $previousGroupSummary = $previousReport->group_task_summary ?? [];
        
        // メンバーIDのリストを取得（当月と前月の両方）
        $allUserIds = array_unique(array_merge(
            array_keys($currentMemberSummary),
            array_keys($currentGroupSummary),
            array_keys($previousMemberSummary),
            array_keys($previousGroupSummary)
        ));
        
        foreach ($allUserIds as $userId) {
            // 当月の集計
            $currentNormal = $currentMemberSummary[$userId]['completed_count'] ?? 0;
            $currentGroup = $currentGroupSummary[$userId]['completed_count'] ?? 0;
            $currentTotal = $currentNormal + $currentGroup;
            
            // 前月の集計
            $previousNormal = $previousMemberSummary[$userId]['completed_count'] ?? 0;
            $previousGroup = $previousGroupSummary[$userId]['completed_count'] ?? 0;
            $previousTotal = $previousNormal + $previousGroup;
            
            // ユーザー名取得
            $userName = $currentMemberSummary[$userId]['user_name'] 
                ?? $currentGroupSummary[$userId]['name']
                ?? $previousMemberSummary[$userId]['user_name']
                ?? $previousGroupSummary[$userId]['name']
                ?? 'Unknown';
            
            // 前月データがない場合
            if ($previousTotal == 0) {
                if ($currentTotal > 0) {
                    $changes[] = [
                        'user_name' => $userName,
                        'type' => 'increase',
                        'change_percentage' => 100,
                        'current' => $currentTotal,
                        'previous' => 0,
                    ];
                }
                continue;
            }
            
            // 変化率計算
            $changePercentage = round((($currentTotal - $previousTotal) / $previousTotal) * 100);
            
            // 閾値以上の変化があった場合
            if (abs($changePercentage) >= $threshold) {
                $changes[] = [
                    'user_name' => $userName,
                    'type' => $changePercentage > 0 ? 'increase' : 'decrease',
                    'change_percentage' => $changePercentage,
                    'current' => $currentTotal,
                    'previous' => $previousTotal,
                ];
            }
        }
        
        return $changes;
    }
    
    /**
     * メンバー別概況レポートを生成
     * 
     * @param int $userId ユーザーID
     * @param int $groupId グループID
     * @param string $yearMonth 対象年月（YYYY-MM形式）
     * @return array ['comment' => string, 'task_classification' => array, 'reward_trend' => array, 'tokens_used' => int]
     * @throws \RuntimeException レポート生成失敗時
     */
    public function generateMemberSummary(int $userId, int $groupId, string $yearMonth): array
    {
        // ユーザー・グループの取得
        $user = User::find($userId);
        if (!$user) {
            throw new \RuntimeException("ユーザーID {$userId} が見つかりません");
        }
        
        $group = Group::find($groupId);
        if (!$group) {
            throw new \RuntimeException("グループID {$groupId} が見つかりません");
        }
        
        // 対象月の範囲を計算
        $startDate = Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        // タスクデータ取得
        $taskData = $this->getMemberTaskData($userId, $startDate, $endDate);
        
        // タスクタイトル取得（通常タスクとグループタスクを分離）
        $normalTaskTitles = $this->getMemberNormalTaskTitles($userId, $startDate, $endDate);
        $groupTaskTitles = $this->getMemberGroupTaskTitles($userId, $startDate, $endDate);
        
        // タスク傾向分析（円グラフ用データ - 全タスク）
        $allTaskTitles = array_merge($normalTaskTitles, $groupTaskTitles);
        $taskClassification = $this->classifyMemberTasks($allTaskTitles);
        
        // 報酬推移データ取得（6ヶ月、折れ線グラフ用）
        $rewardTrend = $this->getMemberRewardTrend($userId, $groupId, $yearMonth, 6);
        
        // AIコメント生成
        $commentResult = $this->generateMemberComment($user, $taskData, $normalTaskTitles, $groupTaskTitles, $taskClassification);
        
        $result = [
            'user_name' => $user->name,
            'username' => $user->username,
            'comment' => $commentResult['comment'],
            'task_classification' => $taskClassification,
            'reward_trend' => $rewardTrend,
            'tokens_used' => $commentResult['tokens_used'],
        ];
        
        // デバッグ: 返却データをログ出力
        Log::info('Member summary result', [
            'user_id' => $userId,
            'user_name' => $result['user_name'],
            'username' => $result['username'],
            'has_comment' => !empty($result['comment']),
        ]);
        
        return $result;
    }
    
    /**
     * メンバーのタスクデータを取得
     * 
     * @param int $userId ユーザーID
     * @param Carbon $startDate 開始日
     * @param Carbon $endDate 終了日
     * @return array タスクデータ
     */
    protected function getMemberTaskData(int $userId, Carbon $startDate, Carbon $endDate): array
    {
        // 通常タスク集計
        $normalTasks = DB::table('tasks')
            ->where('user_id', $userId)
            ->where('is_completed', true)
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->whereNull('group_task_id')
            ->whereNull('deleted_at')
            ->count();
        
        // グループタスク集計（件数と報酬を別々に取得）
        $groupTasksCount = DB::table('tasks')
            ->where('user_id', $userId)
            ->where('is_completed', true)
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->whereNotNull('group_task_id')
            ->whereNull('deleted_at')
            ->count();
        
        $groupTasksReward = DB::table('tasks')
            ->where('user_id', $userId)
            ->where('is_completed', true)
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->whereNotNull('group_task_id')
            ->whereNull('deleted_at')
            ->sum('reward');
        
        return [
            'normal_tasks_count' => $normalTasks,
            'group_tasks_count' => $groupTasksCount,
            'total_reward' => $groupTasksReward ?? 0,
        ];
    }
    
    /**
     * メンバーの通常タスクタイトル一覧を取得
     * 
     * @param int $userId ユーザーID
     * @param Carbon $startDate 開始日
     * @param Carbon $endDate 終了日
     * @return array タスクタイトル配列
     */
    protected function getMemberNormalTaskTitles(int $userId, Carbon $startDate, Carbon $endDate): array
    {
        return DB::table('tasks')
            ->where('user_id', $userId)
            ->where('is_completed', true)
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->whereNull('group_task_id')
            ->whereNull('deleted_at')
            ->orderBy('completed_at', 'desc')
            ->pluck('title')
            ->toArray();
    }
    
    /**
     * メンバーのグループタスクタイトル一覧を取得
     * 
     * @param int $userId ユーザーID
     * @param Carbon $startDate 開始日
     * @param Carbon $endDate 終了日
     * @return array タスクタイトル配列
     */
    protected function getMemberGroupTaskTitles(int $userId, Carbon $startDate, Carbon $endDate): array
    {
        return DB::table('tasks')
            ->where('user_id', $userId)
            ->where('is_completed', true)
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->whereNotNull('group_task_id')
            ->whereNull('deleted_at')
            ->orderBy('completed_at', 'desc')
            ->pluck('title')
            ->toArray();
    }
    
    /**
     * メンバーのタスクを分類（円グラフ用データ）
     * 
     * @param array $taskTitles タスクタイトル配列
     * @return array ['labels' => [], 'data' => []]
     */
    protected function classifyMemberTasks(array $taskTitles): array
    {
        if (empty($taskTitles)) {
            return [
                'labels' => ['タスクなし'],
                'data' => [1],
            ];
        }
        
        // 簡易的なキーワード分類
        $categories = [
            '学習' => ['学習', '勉強', '授業', '宿題', '練習'],
            '作業' => ['作業', '作成', '制作', '実装', '開発'],
            '確認' => ['確認', 'チェック', '検証', 'レビュー', '点検'],
            '重要' => ['重要', '急ぎ', '緊急', '至急'],
            'コミュニケーション' => ['連絡', '相談', '報告', '会議', 'ミーティング'],
        ];
        
        $counts = array_fill_keys(array_keys($categories), 0);
        $counts['その他'] = 0;
        
        foreach ($taskTitles as $title) {
            $classified = false;
            foreach ($categories as $category => $keywords) {
                foreach ($keywords as $keyword) {
                    if (mb_strpos($title, $keyword) !== false) {
                        $counts[$category]++;
                        $classified = true;
                        break 2;
                    }
                }
            }
            if (!$classified) {
                $counts['その他']++;
            }
        }
        
        // 0件のカテゴリを除外
        $counts = array_filter($counts, fn($count) => $count > 0);
        
        return [
            'labels' => array_keys($counts),
            'data' => array_values($counts),
        ];
    }
    
    /**
     * メンバーの報酬推移データを取得（折れ線グラフ用）
     * 
     * @param int $userId ユーザーID
     * @param int $groupId グループID
     * @param string $yearMonth 基準年月（YYYY-MM形式）
     * @param int $months 取得月数
     * @return array ['labels' => [], 'data' => []]
     */
    protected function getMemberRewardTrend(int $userId, int $groupId, string $yearMonth, int $months = 6): array
    {
        $endDate = Carbon::createFromFormat('Y-m', $yearMonth)->endOfMonth();
        $startDate = $endDate->copy()->subMonths($months - 1)->startOfMonth();
        
        $labels = [];
        $data = [];
        
        // 月ごとのデータを取得
        $currentMonth = $startDate->copy();
        while ($currentMonth <= $endDate) {
            $monthStart = $currentMonth->copy()->startOfMonth();
            $monthEnd = $currentMonth->copy()->endOfMonth();
            
            // 月次レポートからグループタスクサマリーを取得
            $report = $this->repository->findByGroupAndMonth($groupId, $currentMonth->format('Y-m'));
            
            $reward = 0;
            if ($report && isset($report->group_task_summary[$userId])) {
                $reward = $report->group_task_summary[$userId]['reward'] ?? 0;
            }
            
            $labels[] = $currentMonth->format('y/m');  // Y/m から y/m に変更（2025/11 → 25/11）
            $data[] = $reward;
            
            $currentMonth->addMonth();
        }
        
        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
    
    /**
     * メンバー概況コメント生成
     * 
     * @param User $user ユーザー
     * @param array $taskData タスクデータ
     * @param array $normalTaskTitles 通常タスクタイトル一覧
     * @param array $groupTaskTitles グループタスクタイトル一覧
     * @param array $taskClassification タスク分類結果
     * @return array ['comment' => string, 'tokens_used' => int]
     */
    protected function generateMemberComment(User $user, array $taskData, array $normalTaskTitles, array $groupTaskTitles, array $taskClassification): array
    {
        $normalCount = $taskData['normal_tasks_count'];
        $groupCount = $taskData['group_tasks_count'];
        $totalCount = $normalCount + $groupCount;
        
        // ユーザーのテーマを取得（adult or child）
        $userTheme = $user->theme ?? 'child';
        
        // デバッグログ追加
        Log::info('Member comment generation', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_theme' => $userTheme,
            'theme_from_db' => $user->theme,
        ]);
        
        // テーマに応じてシステムプロンプトを変更
        if ($userTheme === 'adult') {
            $systemPrompt = <<<PROMPT
あなたはメンバーの学習・生活習慣を支援する教師アバターです。

以下の{$user->name}の月次実績データに基づいて、優先順位の高い情報から順にコメントしてください：

【最優先: 報酬情報】
- 獲得報酬: {$taskData['total_reward']}円

【次点: タスク完了状況】
- 通常タスク: {$normalCount}件完了（個人タスク）
- グループタスク: {$groupCount}件完了（チームタスク）
- 合計: {$totalCount}件完了

PROMPT;
        } else {
            $systemPrompt = <<<PROMPT
あなたは子どもの学習・生活習慣を支援する教師アバターです。

以下の{$user->name}の月次実績データに基づいて、優先順位の高い情報から順にコメントしてください：

【最優先: 報酬情報】
- 獲得報酬: {$taskData['total_reward']}円
※子どもにとって次のお小遣いに直結する最重要データです。最初に大きく称賛してください。

【次点: タスク完了状況】
- 通常タスク: {$normalCount}件完了（個人タスク）
- グループタスク: {$groupCount}件完了（チームタスク）
- 合計: {$totalCount}件完了

PROMPT;
        }

        // タスク分類結果を追加
        if (!empty($taskClassification['labels'])) {
            $systemPrompt .= "\n【タスク傾向】";
            foreach ($taskClassification['labels'] as $index => $label) {
                $count = $taskClassification['data'][$index];
                $systemPrompt .= "\n- {$label}: {$count}件";
            }
            // 最多カテゴリを明示
            $maxIndex = array_keys($taskClassification['data'], max($taskClassification['data']))[0];
            $topCategory = $taskClassification['labels'][$maxIndex];
            $systemPrompt .= "\n※最も取り組んだカテゴリ: {$topCategory}";
        }
        
        // 通常タスクの例を追加
        if (!empty($normalTaskTitles)) {
            $systemPrompt .= "\n\n【通常タスクの例】（{$normalCount}件中、最新5件）:";
            foreach (array_slice($normalTaskTitles, 0, 5) as $index => $title) {
                $systemPrompt .= "\n" . ($index + 1) . ". " . $title;
            }
        }
        
        // グループタスクの例を追加
        if (!empty($groupTaskTitles)) {
            $systemPrompt .= "\n\n【グループタスクの例】（{$groupCount}件中、最新5件）:";
            foreach (array_slice($groupTaskTitles, 0, 5) as $index => $title) {
                $systemPrompt .= "\n" . ($index + 1) . ". " . $title;
            }
        }
        
        $systemPrompt .= <<<PROMPT


【コメント作成の重要ルール】
1. コメントは3-5文程度で構成してください
2. 以下の順序で必ず記述してください：
   ① 最初に報酬を称賛（例: 「今月は{$taskData['total_reward']}円もがんばったね！」）
   ② 次にタスク完了状況を評価（例: 「通常タスク{$normalCount}件、グループタスク{$groupCount}件も達成したね！」）
   ③ 最後にタスク傾向へのコメント（例: 「特に{最多カテゴリ}をがんばっていたね」）
PROMPT;
        
        if ($userTheme === 'adult') {
            $systemPrompt .= <<<PROMPT

3. 丁寧語・敬語を使用し、大人に対して適切な言葉遣いで記述してください
4. 具体的な数値（報酬額、タスク件数）を必ず含めてください
5. 200-300文字程度で簡潔にまとめてください
PROMPT;
        } else {
            $systemPrompt .= <<<PROMPT

3. 子どもが喜ぶ、励まされるトーンで記述してください
4. 具体的な数値（報酬額、タスク件数）を必ず含めてください
5. 200-300文字程度で簡潔にまとめてください
PROMPT;
        }

        // ユーザープロンプト
        if ($userTheme === 'adult') {
            $userPrompt = "このメンバーの月次活動について、上記のルール（報酬→タスク完了→タスク傾向の順）に従って、丁寧語・敬語で具体的なコメントを生成してください。";
        } else {
            $userPrompt = "このメンバーの月次活動について、上記のルール（報酬→タスク完了→タスク傾向の順）に従って、子どもが喜ぶ具体的なコメントを生成してください。";
        }

        // OpenAI APIコール
        $result = $this->openAIService->chat($userPrompt, $systemPrompt, 'gpt-4o-mini');
        
        return [
            'comment' => $result['content'] ?? '',
            'tokens_used' => $result['usage']['total_tokens'] ?? 0,
        ];
    }
    
    /**
     * メンバー別概況レポートPDF用データを生成
     * 
     * @param int $userId ユーザーID
     * @param int $groupId グループID
     * @param string $yearMonth 対象年月（YYYY-MM形式）
     * @return array PDF生成用データ
     * @throws \RuntimeException レポート生成失敗時
     */
    public function generateMemberSummaryPdfData(int $userId, int $groupId, string $yearMonth): array
    {
        // ユーザー・グループの取得
        $user = User::find($userId);
        if (!$user) {
            throw new \RuntimeException("ユーザーID {$userId} が見つかりません");
        }
        
        $group = Group::find($groupId);
        if (!$group) {
            throw new \RuntimeException("グループID {$groupId} が見つかりません");
        }
        
        // 対象月の範囲を計算
        $startDate = Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        // タスクデータ取得
        $taskData = $this->getMemberTaskData($userId, $startDate, $endDate);
        
        // タスクタイトル取得（通常タスクとグループタスクを分離）
        $normalTaskTitles = $this->getMemberNormalTaskTitles($userId, $startDate, $endDate);
        $groupTaskTitles = $this->getMemberGroupTaskTitles($userId, $startDate, $endDate);
        
        // タスク傾向分析（円グラフ用データ - 全タスク）
        $allTaskTitles = array_merge($normalTaskTitles, $groupTaskTitles);
        $taskClassification = $this->classifyMemberTasks($allTaskTitles);
        
        // 報酬推移データ取得（6ヶ月、折れ線グラフ用）
        $rewardTrendRaw = $this->getMemberRewardTrend($userId, $groupId, $yearMonth, 6);
        
        // AIコメント生成（トークン消費しない - 既存のコメントを使用する前提）
        // ただし、生成する必要がある場合は呼び出し元で実施済みと仮定
        // ここではgenerateMemberSummaryの結果を再利用する想定
        
        // 前月データ取得（前月比計算用）
        $previousMonth = $startDate->copy()->subMonth();
        $previousTaskData = $this->getMemberTaskData($userId, $previousMonth->startOfMonth(), $previousMonth->endOfMonth());
        
        $currentTotal = $taskData['normal_tasks_count'] + $taskData['group_tasks_count'];
        $previousTotal = $previousTaskData['normal_tasks_count'] + $previousTaskData['group_tasks_count'];
        
        $changePercentage = 0;
        if ($previousTotal > 0) {
            $changePercentage = round((($currentTotal - $previousTotal) / $previousTotal) * 100);
        } elseif ($currentTotal > 0) {
            $changePercentage = 100;
        }
        
        // トップカテゴリ取得
        $topCategory = null;
        if (!empty($taskClassification['labels']) && !empty($taskClassification['data'])) {
            $maxIndex = array_keys($taskClassification['data'], max($taskClassification['data']))[0];
            $topCategory = $taskClassification['labels'][$maxIndex];
        }
        
        // 円グラフ画像生成（Base64）は実装せず、フロントエンドで生成した画像を受け取る想定
        // または、ここでChart.jsをサーバーサイドで実行する必要がある
        // 簡易実装として、データのみ返却し、PDF生成時にChart.jsで画像化する
        
        return [
            'userName' => $user->name ?: $user->username,
            'yearMonth' => $startDate->format('Y年n月'),
            'comment' => '', // 呼び出し元で設定
            'normalTaskCount' => $taskData['normal_tasks_count'],
            'groupTaskCount' => $taskData['group_tasks_count'],
            'totalTaskCount' => $currentTotal,
            'totalReward' => $taskData['total_reward'],
            'changePercentage' => $changePercentage,
            'topCategory' => $topCategory,
            'taskClassification' => $taskClassification, // 円グラフデータ
            'rewardTrendLabels' => $rewardTrendRaw['labels'], // 折れ線グラフラベル（6ヶ月）
            'rewardTrendData' => $rewardTrendRaw['data'], // 折れ線グラフデータ（6ヶ月）
            'chartImageBase64' => null, // フロントエンド側で画像化して渡す
        ];
    }
}
