<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledTaskTag extends Model
{
    use HasFactory;

    protected $table = 'scheduled_task_tags';

    protected $fillable = [
        'scheduled_task_id',
        'tag_name',
    ];

    /**
     * スケジュールタスクとのリレーション
     */
    public function scheduledTask(): BelongsTo
    {
        return $this->belongsTo(ScheduledGroupTask::class, 'scheduled_task_id');
    }
}