<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 毎月1日午前0時に無料枠をリセット
Schedule::command('tokens:reset-free')
    ->monthlyOn(1, '00:00')
    ->timezone('Asia/Tokyo');

// 毎週日曜日午前2時に古い課金履歴を削除
Schedule::command('payments:clean-old')
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->timezone('Asia/Tokyo');