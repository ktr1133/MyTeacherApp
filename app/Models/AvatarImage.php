<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvatarImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_avatar_id',
        'image_type',
        'expression_type',
        's3_path',
        's3_url',
    ];

    /**
     * TeacherAvatarとのリレーション
     */
    public function teacherAvatar(): BelongsTo
    {
        return $this->belongsTo(TeacherAvatar::class);
    }

    /**
     * 公開URLを取得（s3_urlが空の場合は動的生成）
     */
    public function getPublicUrlAttribute(): ?string
    {
        // DBに保存されている値があればそれを返す
        if ($this->s3_url) {
            return $this->s3_url;
        }

        // なければ動的に生成
        if ($this->s3_path) {
            $baseUrl = rtrim(config('filesystems.disks.s3.url'), '/');
            $generatedUrl = $baseUrl . '/' . $this->s3_path;
            
            return $generatedUrl;
        }

        return null;
    }
}