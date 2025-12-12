<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FCMデバイストークンモデル
 * 
 * ユーザーのデバイス（iOS/Android）ごとのFCMトークンを管理します。
 * Push通知送信時に、アクティブなデバイストークンを取得して使用します。
 * 
 * @property int $id
 * @property int $user_id
 * @property string $device_token
 * @property string $device_type
 * @property string|null $device_name
 * @property string|null $app_version
 * @property bool $is_active
 * @property \Carbon\Carbon|null $last_used_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User $user
 */
class UserDeviceToken extends Model
{
    use HasFactory;

    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'device_token',
        'device_type',
        'device_name',
        'app_version',
        'is_active',
        'last_used_at',
    ];

    /**
     * キャストする属性
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ユーザーとのリレーション
     *
     * @return BelongsTo<User, UserDeviceToken>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * アクティブなデバイストークンのみ取得するスコープ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('last_used_at', '>=', now()->subDays(30));
    }

    /**
     * 特定デバイス種別のみ取得するスコープ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type 'ios' または 'android'
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDeviceType($query, string $type)
    {
        return $query->where('device_type', $type);
    }
}
