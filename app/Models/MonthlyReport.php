<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 月次実績レポート
 * 
 * グループメンバーのタスク達成状況を月次で集計・保存
 */
class MonthlyReport extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'report_month',
        'generated_at',
        'member_task_summary',
        'group_task_completed_count',
        'group_task_total_reward',
        'group_task_details',
        'normal_task_count_previous_month',
        'group_task_count_previous_month',
        'reward_previous_month',
        'pdf_path',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'report_month' => 'date',
        'generated_at' => 'datetime',
        'member_task_summary' => 'array',
        'group_task_details' => 'array',
        'group_task_completed_count' => 'integer',
        'group_task_total_reward' => 'integer',
        'normal_task_count_previous_month' => 'integer',
        'group_task_count_previous_month' => 'integer',
        'reward_previous_month' => 'integer',
    ];

    /**
     * このレポートが属するグループ
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
