<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledTaskExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'scheduled_task_id',
        'created_task_id',
        'deleted_task_id',
        'executed_at',
        'status',
        'note',
        'error_message',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
    ];

    /**
     * スケジュールタスクとのリレーション
     */
    public function scheduledTask(): BelongsTo
    {
        return $this->belongsTo(ScheduledGroupTask::class, 'scheduled_task_id');
    }

    /**
     * 作成されたタスクとのリレーション
     */
    public function createdTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'created_task_id');
    }

    /**
     * 削除されたタスクとのリレーション
     */
    public function deletedTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'deleted_task_id');
    }

    /**
     * スコープ: 成功した実行のみ
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * スコープ: 失敗した実行のみ
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * スコープ: スキップされた実行のみ
     */
    public function scopeSkipped($query)
    {
        return $query->where('status', 'skipped');
    }
}