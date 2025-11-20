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
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'adopted_proposed_tasks_json',
        'was_adopted',
        'adopted_task_ids',
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
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
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

    /**
     * トークン使用量を取得
     */
    public function getTokenUsage(): array
    {
        return [
            'prompt_tokens' => $this->prompt_tokens,
            'completion_tokens' => $this->completion_tokens,
            'total_tokens' => $this->total_tokens,
        ];
    }

    /**
     * トークンコストを計算（概算）
     * GPT-4o-miniの場合: 入力$0.150/1M, 出力$0.600/1M
     */
    public function estimateCost(): float
    {
        $inputCostPer1M = 0.150;
        $outputCostPer1M = 0.600;

        $inputCost = ($this->prompt_tokens / 1000000) * $inputCostPer1M;
        $outputCost = ($this->completion_tokens / 1000000) * $outputCostPer1M;

        return round($inputCost + $outputCost, 6);
    }
}