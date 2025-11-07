<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Repositories\Batch\ScheduledTaskRepositoryInterface;

class ListScheduledTasks extends Command
{
    /**
     * コマンド名
     */
    protected $signature = 'batch:list-tasks {--group= : グループID}';

    /**
     * コマンド説明
     */
    protected $description = 'スケジュールタスクの一覧を表示します';

    /**
     * コンストラクタ
     */
    public function __construct(
        private ScheduledTaskRepositoryInterface $scheduledTaskRepository
    ) {
        parent::__construct();
    }

    /**
     * コマンド実行
     */
    public function handle(): int
    {
        $groupId = $this->option('group');

        try {
            if ($groupId) {
                $scheduledTasks = $this->scheduledTaskRepository->getByGroupId((int) $groupId);
                $this->info("グループ {$groupId} のスケジュールタスク一覧:");
            } else {
                $scheduledTasks = $this->scheduledTaskRepository->getAllActive();
                $this->info('全スケジュールタスク一覧:');
            }

            if ($scheduledTasks->isEmpty()) {
                $this->warn('スケジュールタスクが見つかりませんでした。');
                return Command::SUCCESS;
            }

            $rows = [];
            foreach ($scheduledTasks as $task) {
                $scheduleText = $this->formatSchedule($task->schedules);
                
                $rows[] = [
                    $task->id,
                    $task->title,
                    $scheduleText,
                    $task->is_active ? '✓' : '✗',
                    $task->start_date->format('Y-m-d'),
                    $task->end_date?->format('Y-m-d') ?? '無期限',
                ];
            }

            $this->table(
                ['ID', 'タイトル', 'スケジュール', '有効', '開始日', '終了日'],
                $rows
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('一覧の取得に失敗しました');
            $this->error($e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * スケジュールをフォーマット
     */
    private function formatSchedule(array $schedules): string
    {
        $formatted = [];
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];

        foreach ($schedules as $schedule) {
            $type = $schedule['type'];
            $time = $schedule['time'] ?? '';

            if ($type === 'daily') {
                $formatted[] = "毎日 {$time}";
            } elseif ($type === 'weekly') {
                $days = collect($schedule['days'] ?? [])
                    ->map(fn($d) => $weekdays[$d] ?? '')
                    ->join('・');
                $formatted[] = "毎週{$days} {$time}";
            } elseif ($type === 'monthly') {
                $dates = implode(',', $schedule['dates'] ?? []);
                $formatted[] = "毎月{$dates}日 {$time}";
            }
        }

        return implode(' / ', $formatted);
    }
}