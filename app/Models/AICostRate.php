<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AI コスト換算レートモデル
 *
 * @property int $id
 * @property string $service_type
 * @property string|null $service_detail
 * @property string|null $image_size
 * @property float $unit_cost_usd
 * @property int $token_conversion_rate
 * @property bool $is_active
 * @property \Carbon\Carbon|null $effective_from
 * @property string|null $note
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class AICostRate extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * テーブル名を明示的に指定
     *
     * @var string
     */
    protected $table = 'ai_cost_rates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'service_type',
        'service_detail',
        'image_size',
        'unit_cost_usd',
        'token_conversion_rate',
        'is_active',
        'effective_from',
        'note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'unit_cost_usd' => 'decimal:6',
        'token_conversion_rate' => 'integer',
        'is_active' => 'boolean',
        'effective_from' => 'datetime',
    ];

    /**
     * 有効な換算レートのみ取得
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('effective_from')
                           ->orWhere('effective_from', '<=', now());
                     });
    }

    /**
     * サービスタイプと画像サイズで検索
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $serviceType
     * @param string|null $imageSize
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForService($query, string $serviceType, ?string $imageSize = null)
    {
        $query->where('service_type', $serviceType);
        
        if ($imageSize) {
            $query->where('image_size', $imageSize);
        }
        
        return $query;
    }

    /**
     * AIUsageLogとのリレーション
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function usageLogs()
    {
        return $this->hasMany(AIUsageLog::class, 'cost_rate_id');
    }
}