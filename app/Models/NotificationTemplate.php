<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 通知テンプレートモデル
 * 
 * 管理者が作成する通知のマスターデータを管理。
 * user_notifications テーブルを介してユーザーごとの既読状態を管理する。
 * 
 * @property int $id
 * @property int $sender_id 送信者（管理者）のユーザーID
 * @property string $source 発信元（system/admin）
 * @property string $type 通知種別
 * @property string $priority 優先度（info/normal/important）
 * @property string $title タイトル
 * @property string $message 本文
 * @property array|null $data 追加データ（JSON）
 * @property string|null $action_url アクションURL
 * @property string|null $action_text アクションボタンのテキスト
 * @property string|null $official_page_slug 公式ページのスラッグ
 * @property string $target_type 配信対象タイプ（all/users/groups）
 * @property array|null $target_ids 配信対象IDリスト
 * @property \Carbon\Carbon|null $publish_at 公開開始日時
 * @property \Carbon\Carbon|null $expire_at 公開終了日時
 * @property int|null $updated_by 最終編集者のユーザーID
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * 
 * @property-read User $sender 送信者（管理者）
 * @property-read User|null $updatedBy 最終編集者
 * @property-read \Illuminate\Database\Eloquent\Collection|UserNotification[] $userNotifications
 * 
 * @package App\Models
 */
class NotificationTemplate extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * マスアサインメント可能な属性
     *
     * @var array<string>
     */
    protected $fillable = [
        'sender_id',
        'source',
        'type',
        'priority',
        'title',
        'message',
        'data',
        'action_url',
        'action_text',
        'official_page_slug',
        'target_type',
        'target_ids',
        'publish_at',
        'expire_at',
        'updated_by',
    ];

    /**
     * キャストする属性
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'target_ids' => 'array',
        'publish_at' => 'datetime',
        'expire_at' => 'datetime',
    ];

    /**
     * 送信者（管理者）とのリレーション
     *
     * @return BelongsTo
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * 最終編集者とのリレーション
     *
     * @return BelongsTo
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * ユーザー通知とのリレーション
     *
     * @return HasMany
     */
    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    /**
     * システム通知のみ取得
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSystem($query)
    {
        return $query->where('source', 'system');
    }

    /**
     * 管理者通知のみ取得
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAdmin(Builder $query): Builder
    {
        return $query->where('source', 'admin');
    }

    /**
     * 公開中の通知のみ取得
     * 
     * publish_at が現在時刻以前かつ expire_at が現在時刻以降の通知を取得。
     * publish_at または expire_at が null の場合は常に公開中とみなす。
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished(Builder $query): Builder
    {
        $now = now();
        return $query->where(function ($q) use ($now) {
            $q->whereNull('publish_at')
              ->orWhere('publish_at', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('expire_at')
              ->orWhere('expire_at', '>=', $now);
        });
    }

    /**
     * 優先度順にソート（重要 → 通常 → 情報）
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePriority(Builder $query): Builder
    {
        return $query->orderByRaw("FIELD(priority, 'important', 'normal', 'info')");
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

    /**
     * アイコンのCSSクラスを取得（優先度またはタイプベース）
     *
     * @return string Tailwind CSSのテキストカラークラス
     */
    public function getIconClassAttribute(): string
    {
        if ($this->source === 'admin') {
            return match($this->priority) {
                'important' => 'text-red-500',
                'normal' => 'text-blue-500',
                'info' => 'text-gray-500',
                default => 'text-gray-500',
            };
        }

        return match($this->type) {
            'token_low' => 'text-yellow-500',
            'token_depleted' => 'text-red-500',
            'payment_success' => 'text-green-500',
            'payment_failed' => 'text-red-500',
            'group_task_created' => 'text-blue-500',
            'group_task_assigned' => 'text-purple-500',
            default => 'text-gray-500',
        };
    }

    /**
     * 背景色のCSSクラスを取得（優先度ベース）
     *
     * @return string Tailwind CSSの背景色クラス
     */
    public function getBgClassAttribute(): string
    {
        return match($this->priority) {
            'important' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-700',
            'normal' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700',
            'info' => 'bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700',
            default => 'bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700',
        };
    }

    /**
     * ボーダーのCSSクラスを取得
     *
     * @return string カスタムCSSクラス名
     */
    public function getBorderClassAttribute(): string
    {
        return match($this->priority) {
            'important' => 'notification-border-important',
            'normal' => 'notification-border-normal',
            'info' => 'notification-border-info',
            default => 'notification-border-info',
        };
    }

    /**
     * 管理者通知かどうかを判定
     *
     * @return bool
     */
    public function isAdminNotification(): bool
    {
        return $this->source === 'admin';
    }

    /**
     * 公式ページのURLを取得
     *
     * @return string|null
     */
    public function getOfficialPageUrlAttribute(): ?string
    {
        return $this->official_page_slug 
            ? route('official.announcements.show', $this->official_page_slug)
            : null;
    }
}