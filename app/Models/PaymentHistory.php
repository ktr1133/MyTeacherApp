<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 課金履歴モデル
 * 
 * Stripeでの決済履歴を記録します（1年間保持）。
 */
class PaymentHistory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payable_type',
        'payable_id',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'stripe_invoice_id',
        'token_package_id',
        'amount',
        'token_amount',
        'status',
        'payment_method_type',
        'payment_method_last4',
        'stripe_metadata',
        'failure_message',
        'refund_amount',
        'refunded_at',
        'refund_reason',
    ];

    protected $casts = [
        'stripe_metadata' => 'array',
        'refunded_at' => 'datetime',
    ];

    /**
     * 課金者とのリレーション（Polymorphic）
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * トークンパッケージとのリレーション
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(TokenPackage::class, 'token_package_id');
    }

    /**
     * ステータスの日本語名取得
     */
    public function getStatusNameAttribute(): string
    {
        return match($this->status) {
            'pending' => '処理中',
            'succeeded' => '成功',
            'failed' => '失敗',
            'refunded' => '返金済み',
            default => '不明',
        };
    }

    /**
     * ステータスのバッジクラス取得
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'succeeded' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            'refunded' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}