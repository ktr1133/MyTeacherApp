<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Batch\ScheduledTaskServiceInterface;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExecuteScheduledTaskById extends Command
{
    /**
     * コマンド名
     */
    protected $signature = 'batch:execute-task {id : スケジュールタスクのID}';

    /**
     * コマンド説明
     */
    protected $description = '指定されたスケジュールタスクを手動で実行します';

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
        $id = $this->argument('id');

        $this->info("スケジュールタスク (ID: {$id}) を実行します...");

        try {
            $result = $this->scheduledTaskService->executeScheduledTaskById(
                (int) $id,
                Carbon::now()
            );

            if ($result['success']) {
                $this->info('✓ タスクの作成に成功しました');
                
                if (isset($result['task_id'])) {
                    $this->line("作成されたタスクID: {$result['task_id']}");
                }
                
                if (isset($result['assigned_user'])) {
                    $this->line("担当者: {$result['assigned_user']}");
                }

                Log::info('Scheduled task executed manually', [
                    'scheduled_task_id' => $id,
                    'result' => $result,
                ]);

                return Command::SUCCESS;
            } else {
                $this->warn('タスクはスキップされました');
                $this->line("理由: {$result['message']}");

                return Command::SUCCESS;
            }

        } catch (\Exception $e) {
            $this->error('✗ タスクの実行に失敗しました');
            $this->error($e->getMessage());

            Log::error('Failed to execute scheduled task manually', [
                'scheduled_task_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return Command::FAILURE;
        }
    }
}