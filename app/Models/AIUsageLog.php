<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * AI 使用ログモデル
 *
 * @property int $id
 * @property string $usable_type
 * @property int $usable_id
 * @property int $user_id
 * @property string $service_type
 * @property string|null $service_detail
 * @property float $units_used
 * @property float $cost_usd
 * @property int $token_cost
 * @property int|null $cost_rate_id
 * @property array|null $request_data
 * @property array|null $response_data
 * @property \Carbon\Carbon $created_at
 */
class AIUsageLog extends Model
{
    use HasFactory;

    /**
     * テーブル名を明示的に指定
     *
     * @var string
     */
    protected $table = 'ai_usage_logs';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'usable_type',
        'usable_id',
        'user_id',
        'service_type',
        'service_detail',
        'units_used',
        'cost_usd',
        'token_cost',
        'cost_rate_id',
        'request_data',
        'response_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'units_used' => 'decimal:2',
        'cost_usd' => 'decimal:6',
        'token_cost' => 'integer',
        'request_data' => 'array',
        'response_data' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Userとのリレーション
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * AICostRateとのリレーション
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function costRate()
    {
        return $this->belongsTo(AICostRate::class, 'cost_rate_id');
    }

    /**
     * ポリモーフィックリレーション（使用元モデル）
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function usable()
    {
        return $this->morphTo();
    }
}