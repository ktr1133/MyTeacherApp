<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * トークン購入リクエストモデル
 * 
 * 子どもがトークンを購入する際、親の承認が必要な場合のリクエストを管理します。
 */
class TokenPurchaseRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'package_id',
        'status',
        'approved_by_user_id',
        'approved_at',
        'rejection_reason',
    ];
    
    protected $casts = [
        'approved_at' => 'datetime',
    ];
    
    /**
     * リクエストした子ども
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * 購入希望のパッケージ
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(TokenPackage::class);
    }
    
    /**
     * 承認または却下を行った親
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
    
    /**
     * 承認待ちかどうか
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
    
    /**
     * 承認済みかどうか
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
    
    /**
     * 却下されたかどうか
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
    
    /**
     * 承認待ちのリクエストを取得（スコープ）
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}