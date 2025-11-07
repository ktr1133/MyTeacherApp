<?php
// filepath: app/Models/Holiday.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'name',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * 指定日が祝日かチェック
     */
    public static function isHoliday(\DateTime $date): bool
    {
        $dateString = $date->format('Y-m-d');
        
        // キャッシュを使用（1日間）
        return Cache::remember("holiday:{$dateString}", 86400, function () use ($dateString) {
            return self::where('date', $dateString)->exists();
        });
    }

    /**
     * 次の営業日を取得
     */
    public static function getNextBusinessDay(\DateTime $date): \DateTime
    {
        $nextDay = clone $date;
        
        do {
            $nextDay->modify('+1 day');
        } while (self::isHoliday($nextDay) || $nextDay->format('w') == 0 || $nextDay->format('w') == 6);
        
        return $nextDay;
    }

    /**
     * 年の祝日を一括取得してキャッシュ
     */
    public static function cacheYearHolidays(int $year): void
    {
        $holidays = self::whereYear('date', $year)->pluck('date')->toArray();
        
        foreach ($holidays as $holiday) {
            Cache::put("holiday:{$holiday}", true, 86400);
        }
    }
}