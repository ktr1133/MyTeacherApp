<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ScheduledGroupTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'group_id',
        'created_by',
        'title',
        'description',
        'requires_image',
        'requires_approval',
        'reward',
        'assigned_user_id',
        'auto_assign',
        'schedules',
        'due_duration_days',
        'due_duration_hours',
        'start_date',
        'end_date',
        'skip_holidays',
        'move_to_next_business_day',
        'delete_incomplete_previous',
        'is_active',
        'paused_at',
    ];

    protected $casts = [
        'schedules' => 'array',
        'requires_image' => 'boolean',
        'requires_approval' => 'boolean',
        'auto_assign' => 'boolean',
        'skip_holidays' => 'boolean',
        'move_to_next_business_day' => 'boolean',
        'delete_incomplete_previous' => 'boolean',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'paused_at' => 'datetime',
    ];

    /**
     * グループとのリレーション
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * 作成者とのリレーション
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 担当者とのリレーション
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * タグとのリレーション
     */
    public function tags(): HasMany
    {
        return $this->hasMany(ScheduledTaskTag::class, 'scheduled_task_id');
    }

    /**
     * 実行履歴とのリレーション
     */
    public function executions(): HasMany
    {
        return $this->hasMany(ScheduledTaskExecution::class, 'scheduled_task_id');
    }

    /**
     * スケジュールを一時停止
     */
    public function pause(): bool
    {
        return $this->update([
            'is_active' => false,
            'paused_at' => now(),
        ]);
    }

    /**
     * スケジュールを再開
     */
    public function resume(): bool
    {
        return $this->update([
            'is_active' => true,
            'paused_at' => null,
        ]);
    }

    /**
     * スケジュールが有効期限内かチェック
     */
    public function isInActivePeriod(\DateTime $date = null): bool
    {
        $date = $date ?? now();
        
        if ($this->start_date > $date) {
            return false;
        }
        
        if ($this->end_date && $this->end_date < $date) {
            return false;
        }
        
        return true;
    }

    /**
     * タグ名の配列を取得
     */
    public function getTagNames(): array
    {
        // ⚠️ 一時的な対応: リレーションキャッシュを使わず、常にDBから取得
        // 理由: テスト環境でwith(['tags'])が機能しない問題を回避
        // TODO: 原因究明後、Eloquentリレーション経由に変更
        return DB::table('scheduled_task_tags')
            ->where('scheduled_task_id', $this->id)
            ->pluck('tag_name')
            ->toArray();
    }

    /**
     * スコープ: アクティブなスケジュールのみ
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('paused_at');
    }

    /**
     * スコープ: 今日実行すべきスケジュール
     */
    public function scopeShouldRunToday($query)
    {
        $today = now();
        
        return $query->active()
            ->where('start_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $today);
            });
    }
}