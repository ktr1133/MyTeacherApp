<?php

namespace App\Console\Commands;

use App\Services\Batch\ScheduledTaskServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CreateScheduledGroupTasks extends Command
{
    /**
     * コマンドのシグネチャ
     *
     * @var string
     */
    protected $signature = 'scheduled-tasks:execute 
                            {--date= : 実行日時 (Y-m-d H:i形式、省略時は現在時刻)}
                            {--dry-run : 実際には実行せずに実行予定を表示}';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = 'スケジュール設定に基づいてグループタスクを自動作成します';

    /**
     * コマンドを実行
     */
    public function handle(ScheduledTaskServiceInterface $scheduledTaskService): int
    {
        $this->info('スケジュールタスク実行開始');

        // 実行日時の取得
        $dateOption = $this->option('date');
        $date = $dateOption ? new \DateTime($dateOption) : now();
        
        $this->info("実行日時: " . $date->format('Y-m-d H:i:s'));

        // Dry-runモードの確認
        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->warn('【DRY-RUNモード】実際のタスク作成は行いません');
        }

        try {
            $startTime = microtime(true);

            if ($isDryRun) {
                // Dry-runモードでは実行予定のみ表示
                $this->displayScheduledTasks($date);
                $results = ['success' => 0, 'failed' => 0, 'skipped' => 0];
            } else {
                // 実際にタスクを作成
                $results = $scheduledTaskService->executeScheduledTasks($date);
            }

            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            // 結果表示
            $this->newLine();
            $this->info('========================================');
            $this->info('実行結果');
            $this->info('========================================');
            $this->line("成功: <fg=green>{$results['success']}</> 件");
            $this->line("失敗: <fg=red>{$results['failed']}</> 件");
            $this->line("スキップ: <fg=yellow>{$results['skipped']}</> 件");
            $this->line("実行時間: {$executionTime} 秒");
            $this->info('========================================');

            Log::info('Scheduled tasks execution completed', [
                'date' => $date->format('Y-m-d H:i:s'),
                'results' => $results,
                'execution_time' => $executionTime,
                'dry_run' => $isDryRun,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('エラーが発生しました: ' . $e->getMessage());
            
            Log::error('Scheduled tasks execution failed', [
                'date' => $date->format('Y-m-d H:i:s'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Dry-run用: 実行予定のスケジュールを表示
     */
    protected function displayScheduledTasks(\DateTime $date): void
    {
        $scheduledTasks = \App\Models\ScheduledGroupTask::shouldRunToday()->get();

        if ($scheduledTasks->isEmpty()) {
            $this->warn('実行予定のスケジュールタスクはありません。');
            return;
        }

        $this->newLine();
        $this->info('実行予定のスケジュールタスク:');
        $this->newLine();

        $tableData = [];
        foreach ($scheduledTasks as $task) {
            $tableData[] = [
                'ID' => $task->id,
                'タイトル' => $task->title,
                'グループ' => $task->group->name ?? 'N/A',
                'スケジュール' => $this->formatSchedules($task->schedules),
                '担当者' => $task->auto_assign ? 'ランダム' : ($task->assignedUser->name ?? '未設定'),
                'ステータス' => $task->is_active ? '有効' : '無効',
            ];
        }

        $this->table(
            ['ID', 'タイトル', 'グループ', 'スケジュール', '担当者', 'ステータス'],
            $tableData
        );
    }

    /**
     * スケジュール情報を整形
     */
    protected function formatSchedules(array $schedules): string
    {
        $formatted = [];

        foreach ($schedules as $schedule) {
            $type = $schedule['type'] ?? '';
            $time = $schedule['time'] ?? '';

            switch ($type) {
                case 'daily':
                    $formatted[] = "毎日 {$time}";
                    break;
                case 'weekly':
                    $days = $this->formatWeekdays($schedule['days'] ?? []);
                    $formatted[] = "{$days} {$time}";
                    break;
                case 'monthly':
                    $dates = implode(',', $schedule['dates'] ?? []);
                    $formatted[] = "毎月{$dates}日 {$time}";
                    break;
            }
        }

        return implode(' / ', $formatted);
    }

    /**
     * 曜日を日本語に変換
     */
    protected function formatWeekdays(array $days): string
    {
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $formatted = array_map(fn($day) => $weekdays[$day] ?? '', $days);
        return '毎週' . implode('・', $formatted);
    }
}