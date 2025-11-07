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
        Commands\ExecuteScheduledTaskById::class,
        Commands\ListScheduledTasks::class,
    ];

    /**
     * アプリケーションのコマンドスケジュールを定義
     */
    protected function schedule(Schedule $schedule): void
    {
        // ========================================
        // スケジュールタスクの自動実行
        // ========================================
        
        // 開発環境: 毎分実行（テスト用）
        if (app()->environment('local')) {
            $schedule->command('batch:execute-scheduled-tasks')
                ->everyMinute()
                ->withoutOverlapping(5)
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/scheduled-tasks.log'));
        } 
        // 本番環境: 毎時実行
        else {
            $schedule->command('batch:execute-scheduled-tasks')
                ->hourly()
                ->withoutOverlapping(10)
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/scheduled-tasks.log'))
                ->onSuccess(function () {
                    \Illuminate\Support\Facades\Log::info('Scheduled tasks executed successfully via cron');
                })
                ->onFailure(function () {
                    \Illuminate\Support\Facades\Log::error('Scheduled tasks execution failed via cron');
                });
        }

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
        ->name('cache-holidays')
        ->onOneServer();

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
        ->name('cleanup-execution-history')
        ->onOneServer();
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