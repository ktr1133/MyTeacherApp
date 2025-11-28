<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * アプリケーションのコマンドを登録
     *
     * @var array
     */
    protected $commands = [
        Commands\ExecuteScheduledTasks::class,
        Commands\ListScheduledTasks::class,
        Commands\MonitorRedisHealth::class,
        Commands\MonitorDualAuthCommand::class, // Phase 1.5: 並行運用監視
    ];

    /**
     * アプリケーションのコマンドスケジュールを定義
     */
    protected function schedule(Schedule $schedule): void
    {
        // ========================================
        // スケジュールタスクの自動実行
        // ========================================
        
        $schedule->command('batch:execute-scheduled-tasks')
            ->everyMinute();


        // ========================================
        // 祝日データのキャッシュ更新（毎日0時）
        // ========================================
        
        $schedule->call(function () {
            $currentYear = now()->year;
            $nextYear = $currentYear + 1;
            
            \App\Models\Holiday::cacheYearHolidays($currentYear);
            \App\Models\Holiday::cacheYearHolidays($nextYear);
            
            \Illuminate\Support\Facades\Log::info('Holiday cache updated', [
                'years' => [$currentYear, $nextYear],
            ]);
        })
        ->dailyAt('00:00')
        ->name('cache-holidays');

        // ========================================
        // 古い実行履歴の削除（毎週日曜日3時）
        // ========================================
        
        $schedule->call(function () {
            $deleted = \App\Models\ScheduledTaskExecution::where('created_at', '<', now()->subMonths(6))
                ->delete();
            
            \Illuminate\Support\Facades\Log::info('Old execution history cleaned', [
                'deleted_count' => $deleted,
            ]);
        })
        ->weeklyOn(0, '03:00') // 日曜日3時
        ->name('cleanup-execution-history');

        // ========================================
        // 期限切れ通知の削除（毎日深夜3時）
        // ========================================
        $schedule->command('notifications:delete-expired')
            ->dailyAt('03:00')
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/notifications-cleanup.log'));

        // ========================================
        // Redis健全性監視（5分ごと）
        // ========================================
        $schedule->command('redis:monitor')
            ->everyFiveMinutes()
            ->runInBackground();

        // ========================================
        // 古いキャッシュクリア（毎日深夜3時）
        // ========================================
        $schedule->call(function () {
            \Illuminate\Support\Facades\Cache::tags(['dashboard'])->flush();
            \Illuminate\Support\Facades\Log::info('Old dashboard cache cleared');
        })
        ->dailyAt('03:00')
        ->name('clear-old-cache');

        // ========================================
        // Phase 1.5: Breeze + Cognito並行運用監視（5分ごと）
        // 並行運用期間のみ有効化（2025年12月1日〜12月14日）
        // ========================================
        if (now()->between('2025-12-01', '2025-12-14')) {
            $schedule->command('auth:monitor-dual-auth --alert')
                ->everyFiveMinutes()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/dual-auth-monitoring.log'));
        }
    }

    /**
     * アプリケーションのコンソールコマンドを登録
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}