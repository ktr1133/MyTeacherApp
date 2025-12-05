<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ユーザー通知モデル（中間テーブル）
 * 
 * 通知テンプレートとユーザーの多対多リレーションを管理。
 * ユーザーごとの既読状態を保存する。
 * 
 * @property int $id
 * @property int $user_id ユーザーID
 * @property int $notification_template_id 通知テンプレートID
 * @property bool $is_read 既読フラグ
 * @property \Carbon\Carbon|null $read_at 既読日時
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read User $user ユーザー
 * @property-read NotificationTemplate $template 通知テンプレート
 * 
 * @package App\Models
 */
class UserNotification extends Model
{
    use HasFactory;
    /**
     * マスアサインメント可能な属性
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'notification_template_id',
        'is_read',
        'read_at',
    ];

    /**
     * キャストする属性
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * ユーザーとのリレーション
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 通知テンプレートとのリレーション
     *
     * @return BelongsTo
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'notification_template_id');
    }

    /**
     * 通知を既読にする
     *
     * @return void
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * 未読のみ取得
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /**
     * 新しい順に取得
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }
}