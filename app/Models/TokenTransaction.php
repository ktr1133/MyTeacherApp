<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * トークン取引履歴モデル
 * 
 * トークンの消費・購入・調整履歴を記録します。
 */
class TokenTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tokenable_type',
        'tokenable_id',
        'user_id',
        'type',
        'amount',
        'balance_after',
        'reason',
        'related_type',
        'related_id',
        'stripe_payment_intent_id',
        'stripe_metadata',
        'admin_note',
        'admin_user_id',
    ];

    protected $casts = [
        'stripe_metadata' => 'array',
    ];

    /**
     * トークン所有者とのリレーション
     */
    public function tokenable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 実行ユーザーとのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 関連モデルとのリレーション（Task, TaskProposal等）
     */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 管理者とのリレーション
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /**
     * 種別の日本語名取得
     */
    public function getTypeNameAttribute(): string
    {
        return match($this->type) {
            'consume' => '消費',
            'purchase' => '購入',
            'admin_adjust' => '管理者調整',
            'free_reset' => '無料枠リセット',
            'refund' => '返金',
            default => '不明',
        };
    }
}