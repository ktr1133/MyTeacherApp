<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Batch\ScheduledTaskServiceInterface;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExecuteScheduledTasks extends Command
{
    /**
     * コマンド名
     */
    protected $signature = 'batch:execute-scheduled-tasks';

    /**
     * コマンド説明
     */
    protected $description = 'スケジュールされたタスクを実行し、定期タスクを作成します';

    /**
     * コンストラクタ
     */
    public function __construct(
        private ScheduledTaskServiceInterface $scheduledTaskService
    ) {
        parent::__construct();
    }

    /**
     * コマンド実行
     */
    public function handle(): int
    {
        $this->info('スケジュールタスクの実行を開始します...');
        $startTime = microtime(true);

        try {
            $now = Carbon::now();
            $this->info("実行時刻: {$now->format('Y-m-d H:i:s')}");

            // スケジュールされたタスクを実行
            $results = $this->scheduledTaskService->executeScheduledTasks($now);

            // 結果表示
            $this->displayResults($results);

            $executionTime = round(microtime(true) - $startTime, 2);
            $this->info("実行完了 (実行時間: {$executionTime}秒)");

            Log::info('Scheduled tasks executed successfully', [
                'execution_time' => $executionTime,
                'results' => $results,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('スケジュールタスクの実行中にエラーが発生しました');
            $this->error($e->getMessage());

            Log::error('Failed to execute scheduled tasks', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * 実行結果を表示
     */
    private function displayResults(array $results): void
    {
        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line('実行結果');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $totalProcessed = $results['processed'] ?? 0;
        $totalCreated = $results['success'] ?? 0;
        $totalSkipped = $results['skipped'] ?? 0;
        $totalFailed = $results['failed'] ?? 0;

        $this->table(
            ['項目', '件数'],
            [
                ['処理対象', $totalProcessed],
                ['<fg=green>作成成功</>', $totalCreated],
                ['<fg=yellow>スキップ</>', $totalSkipped],
                ['<fg=red>失敗</>', $totalFailed],
            ]
        );

        if (!empty($results['details'])) {
            $this->newLine();
            $this->line('詳細:');
            
            foreach ($results['details'] as $detail) {
                $status = match($detail['status']) {
                    'success' => '<fg=green>✓</>',
                    'skipped' => '<fg=yellow>-</>',
                    'failed' => '<fg=red>✗</>',
                    default => '?',
                };

                $this->line(sprintf(
                    '%s [ID:%d] %s - %s',
                    $status,
                    $detail['scheduled_task_id'],
                    $detail['title'],
                    $detail['message'] ?? ''
                ));
            }
        }

        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}