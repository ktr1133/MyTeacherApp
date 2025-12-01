<?php

namespace App\Services\Report;

use App\Models\Group;
use App\Models\MonthlyReport;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Report\MonthlyReportRepositoryInterface;
use App\Services\AI\OpenAIService;
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
     * @param OpenAIService $openAIService OpenAIサービス
     */
    public function __construct(
        protected MonthlyReportRepositoryInterface $repository,
        protected SubscriptionServiceInterface $subscriptionService,
        protected OpenAIService $openAIService
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
                ->whereNotNull('completed_at')
                ->whereBetween('completed_at', [$startDate, $endDate])
                ->select('id', 'title', 'completed_at')
                ->orderBy('completed_at', 'desc')
                ->get();
            
            $summary[$user->id] = [
                'user_name' => $user->name,
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
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->with(['user:id,name', 'tags:id,name'])
            ->select('id', 'title', 'user_id', 'reward', 'completed_at')
            ->orderBy('completed_at', 'desc')
            ->get();
        
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
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->with(['user:id,name', 'tags:id,name'])
            ->select('id', 'title', 'user_id', 'reward', 'completed_at')
            ->orderBy('completed_at', 'desc')
            ->get();
        
        $summary = [];
        
        foreach ($group->users as $user) {
            $userTasks = $completedGroupTasks->where('user_id', $user->id);
            
            if ($userTasks->isEmpty()) {
                continue;
            }
            
            $summary[$user->id] = [
                'name' => $user->name,
                'completed_count' => $userTasks->count(),
                'reward' => $userTasks->sum('reward'),
                'tasks' => $userTasks->map(fn($task) => [
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
        if ($group->subscription_active === true) {
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
            'group_task_summary' => $report->group_task_summary ?? [],
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
        
        // 過去12ヶ月分のリストを生成
        for ($i = 0; $i < $limit; $i++) {
            $targetMonth = $now->copy()->subMonths($i);
            
            // グループ作成日以降のみ
            if ($targetMonth->lt($groupCreatedAt->startOfMonth())) {
                continue;
            }
            
            $yearMonth = $targetMonth->format('Y-m');
            
            $months[] = [
                'year_month' => $yearMonth,
                'year' => $targetMonth->format('Y'),
                'month' => $targetMonth->format('m'),
                'label' => $targetMonth->format('Y年n月'),
                'has_report' => in_array($yearMonth, $existingYearMonths),
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
        $baseDate = Carbon::createFromFormat('Y-m', $yearMonth);
        $labels = [];
        $memberData = [];
        
        // 基準月から過去N-1ヶ月（合計Nヶ月）のレポートを取得
        for ($i = $months - 1; $i >= 0; $i--) {
            $targetMonth = $baseDate->copy()->subMonths($i);
            $targetYearMonth = $targetMonth->format('Y-m');
            $labels[] = $targetMonth->format('n月');
            
            $report = $this->repository->findByGroupAndMonth($group->id, $targetYearMonth);
            
            if ($report && $report->member_task_summary) {
                foreach ($report->member_task_summary as $userId => $summary) {
                    if (!isset($memberData[$userId])) {
                        $memberData[$userId] = [
                            'name' => $summary['name'] ?? 'Unknown',
                            'normal_tasks' => array_fill(0, $months, 0),
                            'group_tasks' => array_fill(0, $months, 0),
                        ];
                    }
                    
                    $index = $months - 1 - $i;
                    $memberData[$userId]['normal_tasks'][$index] = $summary['completed_count'] ?? 0;
                }
                
                // グループタスク集計（group_task_summaryから）
                if ($report->group_task_summary) {
                    foreach ($report->group_task_summary as $userId => $summary) {
                        if (!isset($memberData[$userId])) {
                            $memberData[$userId] = [
                                'name' => $summary['name'] ?? 'Unknown',
                                'normal_tasks' => array_fill(0, $months, 0),
                                'group_tasks' => array_fill(0, $months, 0),
                            ];
                        }
                        
                        $index = $months - 1 - $i;
                        $memberData[$userId]['group_tasks'][$index] = $summary['completed_count'] ?? 0;
                    }
                }
            }
        }
        
        // Chart.js用のデータセット形式に変換
        $datasets = [];
        $colors = [
            ['rgb(59, 130, 246)', 'rgba(59, 130, 246, 0.5)'],   // blue
            ['rgb(16, 185, 129)', 'rgba(16, 185, 129, 0.5)'],   // green
            ['rgb(251, 146, 60)', 'rgba(251, 146, 60, 0.5)'],   // orange
            ['rgb(168, 85, 247)', 'rgba(168, 85, 247, 0.5)'],   // purple
            ['rgb(236, 72, 153)', 'rgba(236, 72, 153, 0.5)'],   // pink
            ['rgb(250, 204, 21)', 'rgba(250, 204, 21, 0.5)'],   // yellow
        ];
        
        $colorIndex = 0;
        foreach ($memberData as $userId => $data) {
            $color = $colors[$colorIndex % count($colors)];
            
            // 通常タスク
            $datasets[] = [
                'label' => $data['name'] . ' (通常)',
                'data' => $data['normal_tasks'],
                'backgroundColor' => $color[1],
                'borderColor' => $color[0],
                'borderWidth' => 1,
            ];
            
            // グループタスク
            $datasets[] = [
                'label' => $data['name'] . ' (グループ)',
                'data' => $data['group_tasks'],
                'backgroundColor' => $color[1],
                'borderColor' => $color[0],
                'borderWidth' => 1,
                'borderDash' => [5, 5],
            ];
            
            $colorIndex++;
        }
        
        return [
            'labels' => $labels,
            'datasets' => $datasets,
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
        
        // OpenAIサービスを使ってコメント生成
        $result = $this->openAIService->generateMonthlyReportComment($reportData, $personality);
        
        return [
            'comment' => $result['comment'],
            'tokens_used' => $result['usage']['total_tokens'] ?? 0,
        ];
    }
}
