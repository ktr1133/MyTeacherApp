<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * トークンパッケージモデル
 * 
 * 販売するトークン商品を管理します。
 */
class TokenPackage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'token_amount',
        'price',
        'stripe_price_id',
        'stripe_product_id',
        'sort_order',
        'is_active',
        'is_subscription',
        'features',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_subscription' => 'boolean',
        'features' => 'array',
    ];

    /**
     * 有効な商品のみ取得
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * 価格を円表示で取得
     */
    public function getFormattedPriceAttribute(): string
    {
        return '¥' . number_format($this->price);
    }

    /**
     * トークン量を整形表示
     */
    public function getFormattedTokenAmountAttribute(): string
    {
        if ($this->token_amount >= 1000000) {
            return (int)($this->token_amount / 1000000) . 'M';
        }
        if ($this->token_amount >= 1000) {
            return (int)($this->token_amount / 1000) . 'K';
        }
        return number_format($this->token_amount);
    }
}