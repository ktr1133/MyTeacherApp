<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreeTokenSetting extends Model
{
    use HasFactory;

    /**
     * テーブル名
     *
     * @var string
     */
    protected $table = 'free_token_settings';

    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'amount',
    ];

    /**
     * 属性のキャスト
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 現在の無料トークン数を取得
     *
     * @return int
     */
    public static function getAmount(): int
    {
        $setting = self::first();
        return $setting ? $setting->amount : 10000;
    }

    /**
     * 無料トークン数を更新
     *
     * @param int $amount
     * @return void
     */
    public static function updateAmount(int $amount): void
    {
        $setting = self::first();
        if ($setting) {
            $setting->update(['amount' => $amount]);
        } else {
            self::create(['amount' => $amount]);
        }
    }
}