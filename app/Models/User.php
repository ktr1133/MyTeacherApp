<?php

namespace App\Models;

use App\Models\TokenBalance;
use App\Models\Task;
use App\Models\TaskProposal;
use App\Models\TeacherAvatar;
use App\Models\Group;
use App\Models\FreeTokenSetting;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username', 
        'password',
        'group_id',
        'group_edit_flg',
        'is_admin',
        'last_login_at',
    ];
    
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'group_edit_flg' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    /**
     * このユーザーが所有するタスクを取得する。
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
    
    /**
     * このユーザーが作成したAI提案を取得する。
     */
    public function proposals(): HasMany
    {
        return $this->hasMany(TaskProposal::class);
    }

    /**
     * このユーザーが所属するグループを取得する。
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * このユーザーが割り当てたタスクを取得する。
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_by_user_id');
    }

    /**
     * このユーザーが承認したタスクを取得する。
     */
    public function approvedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'approved_by_user_id');
    }


    /**
     * 管理者かどうかを判定
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    /**
     * マスターとして管理しているグループ
     *
     * @return HasOne
     */
    public function masterGroup(): HasOne
    {
        return $this->hasOne(Group::class, 'master_user_id');
    }

    /**
     * グループ編集権限があるかどうか
     *
     * @return bool
     */
    public function canEditGroup(): bool
    {
        return $this->group_edit_flg === true;
    }

    /**
     * グループマスターかどうか
     *
     * @return bool
     */
    public function isGroupMaster(): bool
    {
        if (!$this->group) {
            return false;
        }
        return $this->group->master_user_id === $this->id;
    }

    /**
     * トークン残高とのリレーション
     */
    public function tokenBalance(): MorphOne
    {
        return $this->morphOne(TokenBalance::class, 'tokenable');
    }

    /**
     * 通知とのリレーション
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * トークン残高を取得（存在しない場合は作成）
     */
    public function getOrCreateTokenBalance(): TokenBalance
    {
        // グループモードの場合はグループの残高を返す
        if ($this->token_mode === 'group' && $this->group_id) {
            return $this->group->getOrCreateTokenBalance();
        }

        // 個人モードの場合
        return $this->tokenBalance()->firstOrCreate(
            [
                'tokenable_type' => self::class,
                'tokenable_id' => $this->id
            ],
            [
                'balance' => FreeTokenSetting::getAmount() ?? 10000,
                'free_balance' => FreeTokenSetting::getAmount() ?? 10000,
                'paid_balance' => 0,
                'free_balance_reset_at' => now()->addMonth(),
                'monthly_consumed_reset_at' => now()->addMonth(),
            ]
        );
    }

    /**
     * トークンを消費できるか判定
     *
     * @param int $amount 必要量
     * @return bool
     */
    public function canConsumeTokens(int $amount): bool
    {
        $balance = $this->getOrCreateTokenBalance();
        return $balance->balance >= $amount;
    }

    /**
     * トークンを消費する
     *
     * @param int $amount 消費量
     * @param string $reason 理由
     * @param Model|null $related 関連モデル
     * @return bool 成功の可否
     */
    public function consumeTokens(int $amount, string $reason, $related = null): bool
    {
        $balance = $this->getOrCreateTokenBalance();
        return $balance->consume($amount, $reason, $related, $this->id);
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
     * 未読通知件数を取得
     *
     * @return int
     */
    public function getUnreadNotificationCountAttribute(): int
    {
        return $this->userNotifications()->unread()->count();
    }

    /**
     * 未読通知を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function unreadNotifications()
    {
        return $this->userNotifications()
            ->with('template')
            ->unread()
            ->latest();
    }

    /**
     * 教師アバターとのリレーション
     */
    public function teacherAvatar()
    {
        return $this->hasOne(TeacherAvatar::class);
    }
}