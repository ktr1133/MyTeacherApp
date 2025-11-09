<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * アプリ内通知モデル
 */
class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'action_url',
        'action_text',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * 通知先ユーザーとのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 既読にする
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
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * 新しい順に取得
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * アイコンクラス取得（Tailwind CSS用）
     */
    public function getIconClassAttribute(): string
    {
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
}