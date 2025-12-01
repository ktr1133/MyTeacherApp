<?php

namespace App\Console\Commands;

use App\Services\Report\MonthlyReportServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * 月次レポート自動生成コマンド
 * 
 * 毎月1日2:00に実行され、全グループの前月分のレポートを自動生成する。
 */
class GenerateMonthlyReports extends Command
{
    /**
     * コマンドの名前と引数
     *
     * @var string
     */
    protected $signature = 'reports:generate-monthly
                          {--year-month= : 対象年月（YYYY-MM形式、省略時は先月）}
                          {--group-id= : 特定グループのみ生成（省略時は全グループ）}
                          {--force : 既存レポートを上書き}';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = '全グループの月次レポートを自動生成';

    /**
     * コマンド実行
     *
     * @param MonthlyReportServiceInterface $reportService
     * @return int
     */
    public function handle(MonthlyReportServiceInterface $reportService): int
    {
        $yearMonth = $this->option('year-month') ?? Carbon::now()->subMonth()->format('Y-m');
        $groupId = $this->option('group-id');
        $force = $this->option('force');
        
        $this->info("月次レポート生成を開始します（対象: {$yearMonth}）");
        
        try {
            if ($groupId) {
                // 特定グループのみ生成
                $group = \App\Models\Group::findOrFail($groupId);
                
                $this->info("グループID {$groupId} のレポートを生成中...");
                $report = $reportService->generateMonthlyReport($group, $yearMonth);
                
                $this->info("✅ レポートID {$report->id} を生成しました。");
                
                return Command::SUCCESS;
                
            } else {
                // 全グループ一括生成
                $this->info('全グループのレポートを生成中...');
                
                $result = $reportService->generateReportsForAllGroups($yearMonth);
                
                $this->newLine();
                $this->info("=== 生成結果 ===");
                $this->info("✅ 成功: {$result['success']}件");
                
                if ($result['failed'] > 0) {
                    $this->warn("❌ 失敗: {$result['failed']}件");
                    
                    if (!empty($result['errors'])) {
                        $this->newLine();
                        $this->error("=== エラー詳細 ===");
                        foreach ($result['errors'] as $error) {
                            $this->error("グループID {$error['group_id']} ({$error['group_name']}): {$error['error']}");
                        }
                    }
                    
                    return Command::FAILURE;
                }
                
                $this->info("すべてのレポート生成が完了しました。");
                
                return Command::SUCCESS;
            }
            
        } catch (\Exception $e) {
            $this->error('レポート生成でエラーが発生しました: ' . $e->getMessage());
            
            Log::error('月次レポート自動生成エラー', [
                'year_month' => $yearMonth,
                'group_id' => $groupId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Command::FAILURE;
        }
    }
}
