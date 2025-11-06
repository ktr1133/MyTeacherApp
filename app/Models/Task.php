<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * mass assignable
     *
     * @var array<int,string>
     */
    protected $fillable = [
        'user_id',
        'source_proposal_id',
        'assigned_by_user_id',
        'approved_by_user_id',
        'group_task_id',
        'title',
        'description',
        'span',
        'due_date',
        'priority',
        'reward',
        'requires_approval',
        'requires_image',
        'approved_at',
        'is_completed',
        'completed_at',
    ];

    /**
     * 型変換
     *
     * @var array<string,string>
     */
    protected $casts = [
        'due_date' => 'datetime',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_completed' => 'boolean',
        'requires_approval' => 'boolean',
        'requires_image' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * tags リレーション（中間テーブル名を明示: task_tag）
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'task_tag');
    }

    /**
     * タスクが元になったAI提案
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(TaskProposal::class, 'source_proposal_id');
    }

    /**
     * タスクが元になったAI提案（エイリアス）
     */
    public function sourceProposal(): BelongsTo
    {
        return $this->belongsTo(TaskProposal::class, 'source_proposal_id');
    }

    /**
     * タスク作成者
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * このタスクを割り当てたユーザー
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    /**
     * このタスクを承認したユーザー
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /**
     * このタスクに添付された画像
     */
    public function images(): HasMany
    {
        return $this->hasMany(TaskImage::class);
    }

    /**
     * 指定ユーザーのタスク取得用スコープ
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * タグを同期する補助メソッド（空配列は全解除）
     *
     * @param array<int> $tagIds
     * @return void
     */
    public function syncTags(array $tagIds): void
    {
        $this->tags()->sync($tagIds);
    }

    /**
     * グループタスクかどうか
     */
    public function isGroupTask(): bool
    {
        return (bool) $this->requires_approval;
    }

    /**
     * 編集可能かどうか（グループタスクは編集不可）
     */
    public function canEdit(): bool
    {
        return !$this->isGroupTask();
    }

    /**
     * このタスクが承認待ちかどうか
     */
    public function isPendingApproval(): bool
    {
        return $this->requires_approval && $this->is_completed && !$this->approved_at;
    }

    /**
     * このタスクが承認済みかどうか
     */
    public function isApproved(): bool
    {
        return $this->requires_approval && $this->approved_at !== null;
    }

    /**
     * 完了可能か（画像必須の場合は画像が添付されているか）
     */
    public function canComplete(): bool
    {
        if (!$this->requires_image) {
            return true;
        }
        return $this->images()->count() > 0;
    }

    /**
     * 担当者が見る場合の表示タブ判定
     * - 未完了: false, null
     * - 申請中: true, null (完了済みだが未承認)
     * - 完了済: true, not null (承認済み)
     */
    public function getTabForAssignee(): string
    {
        if (!$this->is_completed) {
            return 'todo';
        }
        
        if ($this->isPendingApproval()) {
            return 'pending';
        }
        
        return 'completed';
    }

    /**
     * 承認者が見る場合に承認待ち一覧に表示すべきか
     * - 自分がタスク作成者（assigned_by_user_id）である
     * - かつ、承認待ち状態である
     * 
     * @param int $approverId 承認者のユーザーID
     * @return bool
     */
    public function shouldShowInApprovalList(int $approverId): bool
    {
        return $this->assigned_by_user_id === $approverId && $this->isPendingApproval();
    }

    /**
     * タスク削除時にピボットの後始末（Force delete のときに detach）
     */
    protected static function booted(): void
    {
        static::deleting(function (Task $task) {
            if (method_exists($task, 'isForceDeleting') ? $task->isForceDeleting() : true) {
                $task->tags()->detach();
                // 画像ファイルも削除
                foreach ($task->images as $image) {
                    \Storage::disk('public')->delete($image->file_path);
                    $image->delete();
                }
            }
        });
    }
}