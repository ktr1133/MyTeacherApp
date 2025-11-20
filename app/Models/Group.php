<?php

namespace App\Models;

use App\Models\FreeTokenSetting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Laravel\Cashier\Billable;

class Group extends Model
{
    use HasFactory, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'master_user_id',
    ];

    /**
     * このグループに所属するユーザーを取得する。
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * このグループのマスターユーザーを取得する。
     */
    public function master(): BelongsTo
    {
        return $this->belongsTo(User::class, 'master_user_id');
    }

    /**
     * このグループで編集権限を持つユーザーを取得する。
     */
    public function editors()
    {
        return $this->users()->where('group_edit_flg', true);
    }

    /**
     * トークン残高とのリレーション
     */
    public function tokenBalance(): MorphOne
    {
        return $this->morphOne(TokenBalance::class, 'tokenable');
    }

    /**
     * トークン残高を取得（存在しない場合は作成）
     */
    public function getOrCreateTokenBalance(): TokenBalance
    {
        return $this->tokenBalance()->firstOrCreate(
            [
                'tokenable_type' => self::class,
                'tokenable_id' => $this->id
            ],
            [
                'balance' => FreeTokenSetting::getAmount(),
                'free_balance' => FreeTokenSetting::getAmount(),
                'paid_balance' => 0,
                'free_balance_reset_at' => now()->addMonth(),
                'monthly_consumed_reset_at' => now()->addMonth(),
            ]
        );
    }
}