<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * トークン残高モデル
 * 
 * ユーザーまたはグループのトークン残高を管理します。
 */
class TokenBalance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tokenable_type',
        'tokenable_id',
        'balance',
        'free_balance',
        'paid_balance',
        'last_free_reset_at',
    ];

    protected $casts = [
        'last_free_reset_at' => 'datetime',
    ];

    /**
     * トークン所有者とのリレーション（User or Group）
     */
    public function tokenable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 残高が枯渇しているか
     */
    public function isDepleted(): bool
    {
        return $this->balance <= 0;
    }

    /**
     * 残高が低下しているか
     */
    public function isLow(): bool
    {
        $threshold = config('const.token.low_threshold', 200000);
        return $this->balance <= $threshold && $this->balance > 0;
    }

    /**
     * 無料枠がリセット可能か（月次）
     */
    public function canResetFreeBalance(): bool
    {
        if (!$this->last_free_reset_at) {
            return true;
        }

        return $this->last_free_reset_at->startOfMonth()->lt(now()->startOfMonth());
    }

    /**
     * 指定量のトークンが利用可能か
     *
     * @param int $amount
     * @return bool
     */
    public function hasEnough(int $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * 無料枠から消費
     *
     * @param int $amount
     * @return int 実際に消費した量
     */
    public function consumeFromFree(int $amount): int
    {
        $consumed = min($this->free_balance, $amount);
        $this->free_balance -= $consumed;
        $this->balance -= $consumed;
        return $consumed;
    }

    /**
     * 有料枠から消費
     *
     * @param int $amount
     * @return int 実際に消費した量
     */
    public function consumeFromPaid(int $amount): int
    {
        $consumed = min($this->paid_balance, $amount);
        $this->paid_balance -= $consumed;
        $this->balance -= $consumed;
        return $consumed;
    }

    /**
     * トークンを追加（有料）
     *
     * @param int $amount
     * @return void
     */
    public function addPaidTokens(int $amount): void
    {
        $this->paid_balance += $amount;
        $this->balance += $amount;
    }

    /**
     * トークンを追加（無料）
     *
     * @param int $amount
     * @return void
     */
    public function addFreeTokens(int $amount): void
    {
        $this->free_balance += $amount;
        $this->balance += $amount;
    }

    /**
     * 無料枠をリセット
     *
     * @return void
     */
    public function resetFreeBalance(): void
    {
        $freeAmount = config('const.token.free_monthly', 1000000);
        
        // 現在の無料残高を減算
        $this->balance -= $this->free_balance;
        
        // 新しい無料枠を設定
        $this->free_balance = $freeAmount;
        $this->balance += $freeAmount;
        
        // リセット日時を記録
        $this->last_free_reset_at = now();
    }

    /**
     * 残高の割合を取得（0-100）
     *
     * @return float
     */
    public function getBalancePercentage(): float
    {
        $total = $this->free_balance + $this->paid_balance;
        if ($total === 0) {
            return 0;
        }
        return ($this->balance / $total) * 100;
    }

    /**
     * 無料枠の割合を取得（0-100）
     *
     * @return float
     */
    public function getFreeBalancePercentage(): float
    {
        if ($this->balance === 0) {
            return 0;
        }
        return ($this->free_balance / $this->balance) * 100;
    }

    /**
     * 有料枠の割合を取得（0-100）
     *
     * @return float
     */
    public function getPaidBalancePercentage(): float
    {
        if ($this->balance === 0) {
            return 0;
        }
        return ($this->paid_balance / $this->balance) * 100;
    }

    /**
     * 次回の無料枠リセット日を取得
     *
     * @return \Carbon\Carbon
     */
    public function getNextResetDate(): \Carbon\Carbon
    {
        if (!$this->last_free_reset_at) {
            return now()->startOfMonth()->addMonth();
        }

        return $this->last_free_reset_at->copy()->startOfMonth()->addMonth();
    }

    /**
     * 残高状態を取得（healthy, low, depleted）
     *
     * @return string
     */
    public function getBalanceStatus(): string
    {
        if ($this->isDepleted()) {
            return 'depleted';
        }

        if ($this->isLow()) {
            return 'low';
        }

        return 'healthy';
    }

    /**
     * 残高状態の色を取得（Tailwindクラス用）
     *
     * @return string
     */
    public function getBalanceStatusColor(): string
    {
        return match($this->getBalanceStatus()) {
            'depleted' => 'red',
            'low' => 'yellow',
            'healthy' => 'green',
            default => 'gray',
        };
    }

    /**
     * 残高状態のテキストを取得
     *
     * @return string
     */
    public function getBalanceStatusText(): string
    {
        return match($this->getBalanceStatus()) {
            'depleted' => 'トークンが不足しています',
            'low' => '残高が少なくなっています',
            'healthy' => '残高は十分です',
            default => '不明',
        };
    }
}