<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvatarComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_avatar_id',
        'event_type',
        'comment_text',
    ];

    public function teacherAvatar(): BelongsTo
    {
        return $this->belongsTo(TeacherAvatar::class);
    }
}