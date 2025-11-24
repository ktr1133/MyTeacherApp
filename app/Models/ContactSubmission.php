<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * お問い合わせ送信モデル
 */
class ContactSubmission extends Model
{
    /**
     * 複数代入可能な属性
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'app_name',
        'user_id',
        'status',
        'admin_note',
    ];

    /**
     * お問い合わせをしたユーザー（ログイン済みの場合）
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 未対応のお問い合わせのスコープ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * 対応中のお問い合わせのスコープ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * 解決済みのお問い合わせのスコープ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * アプリでフィルタリング
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $appName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForApp($query, string $appName)
    {
        return $query->where('app_name', $appName);
    }
}
