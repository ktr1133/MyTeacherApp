<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Services\Group\GroupTaskLimitServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * グループタスク作成数の月次リセットコマンド
 * 
 * 毎月1日に実行され、全グループのgroup_task_count_current_monthを0にリセットし、
 * group_task_count_reset_atを次月の1日に設定する。
 */
class ResetMonthlyGroupTaskCount extends Command
{
    /**
     * コマンドの名前と引数
     *
     * @var string
     */
    protected $signature = 'group:reset-monthly-task-count
                          {--group-id= : 特定グループのみリセット（省略時は全グループ）}';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = 'グループタスク作成数の月次カウンターをリセット';

    /**
     * コマンド実行
     *
     * @param GroupTaskLimitServiceInterface $groupTaskLimitService
     * @return int
     */
    public function handle(GroupTaskLimitServiceInterface $groupTaskLimitService): int
    {
        $this->info('グループタスク作成数の月次リセットを開始します...');
        
        $groupId = $this->option('group-id');
        
        try {
            if ($groupId) {
                // 特定グループのみリセット
                $group = Group::findOrFail($groupId);
                $this->resetGroup($group, $groupTaskLimitService);
                $this->info("グループID {$groupId} のリセットが完了しました。");
            } else {
                // 全グループをリセット
                $groups = Group::all();
                $resetCount = 0;
                
                foreach ($groups as $group) {
                    $this->resetGroup($group, $groupTaskLimitService);
                    $resetCount++;
                }
                
                $this->info("{$resetCount}件のグループをリセットしました。");
            }
            
            Log::info('グループタスク作成数の月次リセット完了', [
                'group_id' => $groupId,
                'timestamp' => now()->toDateTimeString(),
            ]);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('リセット処理でエラーが発生しました: ' . $e->getMessage());
            Log::error('グループタスク月次リセットエラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Command::FAILURE;
        }
    }

    /**
     * 個別グループのリセット処理
     *
     * @param Group $group
     * @param GroupTaskLimitServiceInterface $service
     * @return void
     */
    protected function resetGroup(Group $group, GroupTaskLimitServiceInterface $service): void
    {
        $before = $group->group_task_count_current_month;
        $service->resetMonthlyCount($group);
        
        $this->line("  - [{$group->name}] {$before}件 → 0件");
    }
}
