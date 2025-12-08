<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ========================================
// スケジュールタスクの自動実行（毎分）
// ========================================
Schedule::command('batch:execute-scheduled-tasks')
    ->everyMinute()
    ->withoutOverlapping(10)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduled-tasks.log'))
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Scheduled tasks executed successfully via cron');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Scheduled tasks execution failed via cron');
    });

// ========================================
// 毎月1日午前0時に無料枠をリセット
// ========================================
Schedule::command('tokens:reset-free')
    ->monthlyOn(1, '00:00')
    ->timezone('Asia/Tokyo');

// ========================================
// 毎月1日午前0時にグループタスク作成数をリセット
// ========================================
Schedule::command('group:reset-monthly-task-count')
    ->monthlyOn(1, '00:00')
    ->timezone('Asia/Tokyo')
    ->appendOutputTo(storage_path('logs/group-task-reset.log'))
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('グループタスク月次リセット成功');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('グループタスク月次リセット失敗');
    });

// ========================================
// 毎月1日午前2時に月次レポートを自動生成
// ========================================
Schedule::command('reports:generate-monthly')
    ->monthlyOn(1, '02:00')
    ->timezone('Asia/Tokyo')
    ->appendOutputTo(storage_path('logs/monthly-reports.log'))
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('月次レポート自動生成成功');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('月次レポート自動生成失敗');
    });

// ========================================
// 毎週日曜日午前2時に古い課金履歴を削除
// ========================================
Schedule::command('payments:clean-old')
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->timezone('Asia/Tokyo');

// ========================================
// 祝日データのキャッシュ更新（毎日0時）
// ========================================
Schedule::call(function () {
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
Schedule::call(function () {
    $deleted = \App\Models\ScheduledTaskExecution::where('created_at', '<', now()->subMonths(6))
        ->delete();
    
    \Illuminate\Support\Facades\Log::info('Old execution history cleaned', [
        'deleted_count' => $deleted,
    ]);
})
->weeklyOn(0, '03:00')
->name('cleanup-execution-history');

// ========================================
// 期限切れ通知の削除（毎日深夜3時）
// ========================================
Schedule::command('notifications:delete-expired')
    ->dailyAt('03:00')
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/notifications-cleanup.log'));

// ========================================
// Redis健全性監視（5分ごと）
// ========================================
Schedule::command('redis:monitor')
    ->everyFiveMinutes()
    ->runInBackground();

// ========================================
// 古いキャッシュクリア（毎日深夜3時）
// ========================================
Schedule::call(function () {
    \Illuminate\Support\Facades\Cache::tags(['dashboard'])->flush();
    \Illuminate\Support\Facades\Log::info('Old dashboard cache cleared');
})
->dailyAt('03:00')
->name('clear-old-cache');

// ========================================
// サブスクリプション期間終了クリーンアップ（毎日深夜3時）
// Webhook失敗時のフォールバック処理
// ========================================
Schedule::command('subscription:cleanup-expired')
    ->dailyAt('03:00')
    ->timezone('Asia/Tokyo')
    ->withoutOverlapping()  // 二重実行防止
    ->onOneServer()         // 複数サーバー環境での重複実行防止
    ->appendOutputTo(storage_path('logs/subscription-cleanup.log'))
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('サブスクリプションクリーンアップ成功');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('サブスクリプションクリーンアップ失敗');
    });

// ========================================
// Phase 1.5: Breeze + Cognito並行運用監視（5分ごと）
// 並行運用期間のみ有効化（2025年12月1日〜12月14日）
// ========================================
if (now()->between('2025-12-01', '2025-12-14')) {
    Schedule::command('auth:monitor-dual-auth --alert')
        ->everyFiveMinutes()
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/dual-auth-monitoring.log'));
}