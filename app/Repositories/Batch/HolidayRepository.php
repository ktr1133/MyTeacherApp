<?php

namespace App\Repositories\Batch;

use App\Models\Holiday;
use Illuminate\Support\Facades\Cache;

class HolidayRepository implements HolidayRepositoryInterface
{
    /**
     * 指定日が祝日かチェック
     */
    public function isHoliday(\DateTime $date): bool
    {
        $dateString = $date->format('Y-m-d');
        
        // キャッシュを使用（1日間）
        return Cache::remember("holiday:{$dateString}", 86400, function () use ($dateString) {
            return Holiday::where('date', $dateString)->exists();
        });
    }

    /**
     * 次の営業日を取得
     */
    public function getNextBusinessDay(\DateTime $date): \DateTime
    {
        $nextDay = clone $date;
        
        do {
            $nextDay->modify('+1 day');
        } while (
            $this->isHoliday($nextDay) || 
            $nextDay->format('w') == 0 || // 日曜日
            $nextDay->format('w') == 6    // 土曜日
        );
        
        return $nextDay;
    }

    /**
     * 年の祝日を一括取得してキャッシュ
     */
    public function cacheYearHolidays(int $year): void
    {
        $holidays = Holiday::whereYear('date', $year)
            ->pluck('date')
            ->toArray();
        
        foreach ($holidays as $holiday) {
            Cache::put("holiday:{$holiday}", true, 86400);
        }
    }
}