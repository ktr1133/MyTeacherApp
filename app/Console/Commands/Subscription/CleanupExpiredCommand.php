<?php

namespace App\Console\Commands\Subscription;

use App\Models\Group;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Subscription;

/**
 * 期間終了したサブスクリプションのクリーンアップ
 * 
 * Webhook失敗時のフォールバック処理
 * 毎日深夜3時に実行され、期間終了したサブスクリプションを検出し
 * Groupsテーブルを無料プラン状態にリセットする
 */
class CleanupExpiredCommand extends Command
{
    /**
     * コマンド名と説明
     *
     * @var string
     */
    protected $signature = 'subscription:cleanup-expired';

    /**
     * コマンド説明
     *
     * @var string
     */
    protected $description = '期間終了したサブスクリプションのGroupsテーブルをリセット（Webhookフォールバック）';

    /**
     * コマンド実行
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('期間終了サブスクリプションのクリーンアップを開始します...');
        Log::info('Cron: Cleanup expired subscriptions started');
        
        $processedCount = 0;
        
        // 期間終了したサブスクリプションを取得
        $expiredSubscriptions = Subscription::where('stripe_status', 'canceled')
            ->where('ends_at', '<', now())
            ->get();
        
        $this->info("対象サブスクリプション: {$expiredSubscriptions->count()}件");
        
        foreach ($expiredSubscriptions as $subscription) {
            $group = Group::find($subscription->user_id); // user_id = Group ID
            
            if (!$group) {
                Log::warning('Cron: Group not found', [
                    'subscription_id' => $subscription->id,
                    'group_id' => $subscription->user_id,
                ]);
                $this->warn("グループID {$subscription->user_id}: グループが見つかりません");
                continue;
            }
            
            // 既にリセット済みならスキップ（冪等性）
            if (!$group->subscription_active) {
                $this->line("グループID {$group->id}: 既にリセット済み");
                continue;
            }
            
            // Groupsテーブルをリセット
            try {
                DB::transaction(function () use ($group, $subscription) {
                    $group->update([
                        'subscription_active' => false,
                        'subscription_plan' => null,
                        'max_members' => 6,
                        'max_groups' => 1,
                    ]);
                    
                    Log::info('Cron: Groups table reset', [
                        'group_id' => $group->id,
                        'subscription_id' => $subscription->id,
                        'ends_at' => $subscription->ends_at,
                    ]);
                });
                
                $this->info("グループID {$group->id}: リセット完了");
                $processedCount++;
                
            } catch (\Exception $e) {
                Log::error('Cron: Groups reset failed', [
                    'group_id' => $group->id,
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
                
                $this->error("グループID {$group->id}: リセット失敗 - {$e->getMessage()}");
                // 次のGroupの処理を継続
            }
        }
        
        Log::info('Cron: Cleanup completed', [
            'total_processed' => $processedCount,
        ]);
        
        $this->info("クリーンアップ完了: {$processedCount}件のGroupをリセットしました");
        
        return Command::SUCCESS;
    }
}
