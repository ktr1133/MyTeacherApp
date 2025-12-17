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
        Commands\DeleteUnconsentedMinorsCommand::class, // Phase 5-2: 保護者同意未取得アカウント削除
    ];

    /**
     * アプリケーションのコマンドスケジュールを定義
     * 
     * @deprecated Laravel 11以降は routes/console.php で Schedule ファサードを使用
     */
    protected function schedule(Schedule $schedule): void
    {
        // Laravel 11以降、スケジュールは routes/console.php で定義します
        // このメソッドは後方互換性のために残されています
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