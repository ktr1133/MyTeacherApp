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
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Laravel\Cashier\Billable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, Billable, TwoFactorAuthenticatable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'name',
        'password',
        'group_id',
        'group_edit_flg',
        'is_admin',
        'last_login_at',
        'theme',
        'timezone',
        'cognito_sub',
        'auth_provider',
        // セキュリティ関連カラム（Stripe要件対応）
        'is_locked',
        'locked_at',
        'locked_reason',
        'failed_login_attempts',
        'last_failed_login_at',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'allowed_ips',
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
        // セキュリティ関連のキャスト
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
        'failed_login_attempts' => 'integer',
        'last_failed_login_at' => 'datetime',
        'two_factor_enabled' => 'boolean',
        'two_factor_recovery_codes' => 'array',
        'two_factor_confirmed_at' => 'datetime',
        'allowed_ips' => 'array',
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
                'balance' => FreeTokenSetting::getAmount() ?? 25000,
                'free_balance' => FreeTokenSetting::getAmount() ?? 25000,
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

    /**
     * 親ユーザーかどうか（グループマスターまたは編集権を持つ）
     */
    public function isParent(): bool
    {
        if (!$this->group_id) {
            return false;
        }

        // グループマスターの場合
        if ($this->group && $this->group->master_user_id === $this->id) {
            return true;
        }

        // 編集権を持つ場合
        return $this->group_edit_flg;
    }

    /**
     * 子ユーザーかどうか（編集権を持たない）
     */
    public function isChild(): bool
    {
        return $this->group_id && !$this->isParent();
    }

    /**
     * 子ども向けテーマを使用するか
     */
    public function useChildTheme(): bool
    {
        return $this->theme === 'child';
    }

    /**
     * 指定ユーザーのテーマを変更する権限があるか
     */
    public function canChangeThemeOf(User $targetUser): bool
    {
        // 自分自身のテーマは変更可能
        if ($this->id === $targetUser->id) {
            return true;
        }

        // 親が子のテーマを変更可能
        if ($this->isParent() && $targetUser->isChild() && $this->group_id === $targetUser->group_id) {
            return true;
        }

        return false;
    }

    /**
     * トークン購入時に親の承認が必要かどうか
     */
    public function requiresPurchaseApproval(): bool
    {
        return $this->isChild() && $this->requires_purchase_approval;
    }

    /**
     * トークン購入リクエストとのリレーション
     */
    public function tokenPurchaseRequests(): HasMany
    {
        return $this->hasMany(TokenPurchaseRequest::class);
    }

    /**
     * 承認待ちのトークン購入リクエストを取得
     */
    public function pendingPurchaseRequests()
    {
        return $this->tokenPurchaseRequests()->pending();
    }

    /**
     * 自分の子どものトークン購入リクエストを取得（親用）
     */
    public function childrenPurchaseRequests()
    {
        if (!$this->isParent()) {
            return collect();
        }
        
        return TokenPurchaseRequest::whereHas('user', function ($query) {
            $query->where('group_id', $this->group_id)
                  ->where('id', '!=', $this->id);
        })->pending()->with(['user', 'package'])->get();
    }

    /**
     * ユーザーのタイムゾーンで現在時刻を取得
     *
     * @return \Illuminate\Support\Carbon
     */
    public function nowInUserTimezone()
    {
        return now()->timezone($this->timezone ?? 'Asia/Tokyo');
    }

    /**
     * UTC時刻をユーザーのタイムゾーンに変換
     *
     * @param \Illuminate\Support\Carbon|string $datetime
     * @return \Illuminate\Support\Carbon
     */
    public function toUserTimezone($datetime)
    {
        if (is_string($datetime)) {
            $datetime = \Carbon\Carbon::parse($datetime);
        }
        
        return $datetime->timezone($this->timezone ?? 'Asia/Tokyo');
    }

    /**
     * タイムゾーン名を取得（表示用）
     *
     * @return string
     */
    public function getTimezoneNameAttribute(): string
    {
        $timezones = [
            'Asia/Tokyo' => '日本（東京）',
            'America/New_York' => 'アメリカ東部（ニューヨーク）',
            'America/Los_Angeles' => 'アメリカ西部（ロサンゼルス）',
            'Europe/London' => 'イギリス（ロンドン）',
            'Europe/Paris' => 'フランス（パリ）',
            'Asia/Shanghai' => '中国（上海）',
            'Asia/Singapore' => 'シンガポール',
            'Australia/Sydney' => 'オーストラリア（シドニー）',
        ];
        
        return $timezones[$this->timezone] ?? $this->timezone;
    }
}