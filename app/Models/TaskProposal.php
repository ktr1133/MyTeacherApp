<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskProposal extends Model
{
    use HasFactory;

    /**
     * モデルに関連付けられているテーブル名。
     *
     * @var string
     */
    protected $table = 'task_proposals';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'original_task_text',
        'proposal_context',
        'proposed_tasks_json',
        'model_used',
        'adopted_proposed_tasks_json',
        'was_adopted',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'proposed_tasks_json' => 'array', // JSONカラムを自動的にPHP配列にキャスト
        'adopted_proposed_tasks_json' => 'array',
        'was_adopted' => 'boolean',
    ];

    /**
     * この提案から作成されたタスクを取得する。
     */
    public function tasks(): HasMany
    {
        // Taskモデルのsource_proposal_idカラムを参照
        return $this->hasMany(Task::class, 'source_proposal_id');
    }

    /**
     * この提案を行ったユーザーを取得する。
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}