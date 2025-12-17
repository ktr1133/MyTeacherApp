<?php

namespace App\Models;

use App\Models\TokenBalance;
use App\Models\Task;
use App\Models\TaskProposal;
use App\Models\TeacherAvatar;
use App\Models\Group;
use App\Models\FreeTokenSetting;
use App\Models\Notification;
use App\Notifications\ResetPasswordNotification;
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
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, Billable, TwoFactorAuthenticatable, SoftDeletes, HasApiTokens;

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
        'notification_settings',
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
        // 未成年者・保護者同意関連（COPPA対応）
        'birthdate',
        'is_minor',
        'parent_email',
        'parent_user_id',
        'parent_consent_token',
        'parent_consented_at',
        'parent_consent_expires_at',
        'parent_invitation_token',
        'parent_invitation_expires_at',
        // 同意管理関連（法令遵守対応）
        'created_by_user_id',
        'consent_given_by_user_id',
        'privacy_policy_version',
        'terms_version',
        'privacy_policy_agreed_at',
        'terms_agreed_at',
        'self_consented_at',
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
        'notification_settings' => 'array',
        // セキュリティ関連のキャスト
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
        'failed_login_attempts' => 'integer',
        'last_failed_login_at' => 'datetime',
        'two_factor_enabled' => 'boolean',
        'two_factor_recovery_codes' => 'array',
        'two_factor_confirmed_at' => 'datetime',
        'allowed_ips' => 'array',
        // 未成年者・保護者同意関連のキャスト
        'birthdate' => 'date',
        'is_minor' => 'boolean',
        'parent_consented_at' => 'datetime',
        'parent_consent_expires_at' => 'datetime',
        'parent_invitation_expires_at' => 'datetime',
        // 同意管理関連のキャスト
        'privacy_policy_agreed_at' => 'datetime',
        'terms_agreed_at' => 'datetime',
        'self_consented_at' => 'datetime',
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
     * このユーザーのデバイストークンを取得する。
     */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(UserDeviceToken::class);
    }

    /**
     * このユーザーのアクティブなデバイストークンを取得する。
     */
    public function activeDeviceTokens(): HasMany
    {
        return $this->hasMany(UserDeviceToken::class)->active();
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
     * グループ編集権限の判定基準:
     * 1. group_edit_flgがtrueの場合
     * 2. グループの管理者（master_user_id）の場合
     *
     * @return bool
     */
    public function canEditGroup(): bool
    {
        // group_edit_flgがtrueの場合
        if ($this->group_edit_flg) {
            return true;
        }
        
        // グループの管理者（master_user_id）の場合
        if ($this->group && $this->group->master_user_id === $this->id) {
            return true;
        }
        
        return false;
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

    /**
     * パスワードリセット通知を送信（カスタム通知を使用）
     *
     * @param string $token パスワードリセットトークン
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    // ================================================================================
    // 未成年者・保護者同意関連メソッド（COPPA対応）
    // ================================================================================

    /**
     * ユーザーが未成年者（13歳未満）かどうかを判定
     * 
     * @return bool 13歳未満の場合true
     */
    public function calculateIsMinor(): bool
    {
        if (!$this->birthdate) {
            return false;
        }

        $age = \Carbon\Carbon::parse($this->birthdate)->age;
        return $age < 13;
    }

    /**
     * 保護者の同意が必要かどうかを判定
     * 
     * @return bool 未成年者で保護者同意未取得の場合true
     */
    public function needsParentConsent(): bool
    {
        // 未成年者フラグがfalseの場合は不要
        if (!$this->is_minor) {
            return false;
        }

        // 保護者同意済みの場合は不要
        if ($this->parent_consented_at) {
            // 有効期限切れでなければ不要
            if (!$this->isParentConsentExpired()) {
                return false;
            }
        }

        return true;
    }

    /**
     * 保護者同意の有効期限が切れているかを判定
     * 
     * @return bool 有効期限切れの場合true
     */
    public function isParentConsentExpired(): bool
    {
        if (!$this->parent_consent_expires_at) {
            return false;
        }

        return \Carbon\Carbon::now()->isAfter($this->parent_consent_expires_at);
    }

    /**
     * 保護者招待トークンの有効期限が切れているかを判定
     * 
     * @return bool 有効期限切れの場合true
     */
    public function isParentInvitationExpired(): bool
    {
        if (!$this->parent_invitation_expires_at) {
            return false;
        }

        return \Carbon\Carbon::now()->isAfter($this->parent_invitation_expires_at);
    }

    // ================================================================================
    // 同意管理関連メソッド（法令遵守対応）
    // ================================================================================

    /**
     * 本人同意かどうかを判定
     * 
     * @return bool 本人同意の場合true（代理同意でない）
     */
    public function isOwnConsent(): bool
    {
        return $this->consent_given_by_user_id === null || $this->consent_given_by_user_id === $this->id;
    }

    /**
     * 代理同意かどうかを判定
     * 
     * @return bool 代理同意の場合true（親が子のアカウントを作成した場合）
     */
    public function isProxyConsent(): bool
    {
        return !$this->isOwnConsent();
    }

    /**
     * プライバシーポリシーの再同意が必要かどうかを判定
     * 
     * @return bool 再同意が必要な場合true
     */
    public function needsPrivacyPolicyReconsent(): bool
    {
        $currentVersion = config('legal.current_versions.privacy_policy');
        
        // 未同意の場合は再同意必要
        if (!$this->privacy_policy_version) {
            return true;
        }
        
        // バージョンが異なる場合は再同意必要
        return $this->privacy_policy_version !== $currentVersion;
    }

    /**
     * 利用規約の再同意が必要かどうかを判定
     * 
     * @return bool 再同意が必要な場合true
     */
    public function needsTermsReconsent(): bool
    {
        $currentVersion = config('legal.current_versions.terms_of_service');
        
        // 未同意の場合は再同意必要
        if (!$this->terms_version) {
            return true;
        }
        
        // バージョンが異なる場合は再同意必要
        return $this->terms_version !== $currentVersion;
    }

    /**
     * いずれかの法的文書の再同意が必要かどうかを判定
     * 
     * @return bool いずれかの再同意が必要な場合true
     */
    public function needsAnyLegalReconsent(): bool
    {
        return $this->needsPrivacyPolicyReconsent() || $this->needsTermsReconsent();
    }

    /**
     * 同意を記録する
     * 
     * @param string $type 同意種別（'privacy_policy' or 'terms'）
     * @param string|null $version バージョン（nullの場合は現行バージョンを使用）
     * @return void
     */
    public function recordLegalConsent(string $type, ?string $version = null): void
    {
        $version = $version ?? config("legal.current_versions.{$type}");
        
        if ($type === 'privacy_policy') {
            $this->privacy_policy_version = $version;
            $this->privacy_policy_agreed_at = now();
        } elseif ($type === 'terms' || $type === 'terms_of_service') {
            $this->terms_version = $version;
            $this->terms_agreed_at = now();
        }
        
        $this->save();
    }

    /**
     * 13歳到達で本人再同意が必要かどうかを判定
     * 
     * @return bool 本人再同意が必要な場合true
     */
    public function needsSelfConsent(): bool
    {
        // 代理同意でない場合は不要
        if (!$this->isProxyConsent()) {
            return false;
        }
        
        // 既に本人同意済みの場合は不要
        if ($this->self_consented_at) {
            return false;
        }
        
        // 13歳未満の場合は不要
        if ($this->calculateIsMinor()) {
            return false;
        }
        
        // 13歳以上で代理同意のまま、本人同意未済の場合は必要
        return true;
    }

    /**
     * 作成者（親ユーザー）とのリレーション
     * 
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * 同意者（代理同意の場合は親ユーザー）とのリレーション
     * 
     * @return BelongsTo
     */
    public function consentGiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consent_given_by_user_id');
    }
}