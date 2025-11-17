<?php
// filepath: /home/ktr/mtdev/laravel/app/Models/TeacherAvatar.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TeacherAvatar extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'seed',
        'sex',
        'hair_color',
        'eye_color',
        'clothing',
        'accessory',
        'body_type',
        'tone',
        'enthusiasm',
        'formality',
        'humor',
        'draw_model_version',
        'estimated_token_usage',
        'is_transparent',
        'generation_status',
        'last_generated_at',
        'is_visible',
    ];

    protected $casts = [
        'last_generated_at' => 'datetime',
        'is_visible' => 'boolean',
    ];

    /**
     * ユーザーとのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 全画像とのリレーション
     */
    public function images(): HasMany
    {
        return $this->hasMany(AvatarImage::class);
    }

    /**
     * コメントとのリレーション
     */
    public function comments(): HasMany
    {
        return $this->hasMany(AvatarComment::class);
    }

    /**
     * 全身画像とのリレーション（HasOne）
     */
    public function fullBodyImage(): HasOne
    {
        return $this->hasOne(AvatarImage::class)
                    ->where('image_type', config('const.avatar_image_types.full_body'))
                    ->where('expression_type', config('const.avatar_expressions.normal'));
    }

    /**
     * バストアップ画像（通常表情）とのリレーション（HasOne）
     */
    public function bustImage(): HasOne
    {
        return $this->hasOne(AvatarImage::class)
                    ->where('image_type', config('const.avatar_image_types.bust'))
                    ->where('expression_type', config('const.avatar_expressions.normal'));
    }

    /**
     * バストアップ画像（喜び表情）とのリレーション（HasOne）
     */
    public function bustImageHappy(): HasOne
    {
        return $this->hasOne(AvatarImage::class)
                    ->where('image_type', config('const.avatar_image_types.bust'))
                    ->where('expression_type', config('const.avatar_expressions.happy'));
    }

    /**
     * バストアップ画像（驚き表情）とのリレーション（HasOne）
     */
    public function bustImageSurprised(): HasOne
    {
        return $this->hasOne(AvatarImage::class)
                    ->where('image_type', config('const.avatar_image_types.bust'))
                    ->where('expression_type', config('const.avatar_expressions.surprised'));
    }

    /**
     * バストアップ画像（怒り表情）とのリレーション（HasOne）
     */
    public function bustImageAngry(): HasOne
    {
        return $this->hasOne(AvatarImage::class)
                    ->where('image_type', config('const.avatar_image_types.bust'))
                    ->where('expression_type', config('const.avatar_expressions.angry'));
    }

    /**
     * バストアップ画像（悲しみ表情）とのリレーション（HasOne）
     */
    public function bustImageSad(): HasOne
    {
        return $this->hasOne(AvatarImage::class)
                    ->where('image_type', config('const.avatar_image_types.bust'))
                    ->where('expression_type', config('const.avatar_expressions.sad'));
    }

    /**
     * 指定イベントのコメントを取得
     */
    public function getCommentForEvent(string $eventType): ?string
    {
        $comment = $this->comments()->where('event_type', $eventType)->first();
        return $comment?->comment_text;
    }

    /**
     * 生成完了しているかチェック
     */
    public function isCompleted(): bool
    {
        return $this->generation_status === config('const.avatar_generation_statuses.completed');
    }

    /**
     * 表示可能かチェック
     */
    public function isVisible(): bool
    {
        return $this->is_visible && $this->isCompleted();
    }

    /**
     * 指定表情のバストアップ画像を取得（汎用メソッド）
     * 
     * @param string $expressionType 表情タイプ（normal, happy, sad, angry, surprised）
     * @return HasOne
     */
    public function bustImageByExpression(string $expressionType): HasOne
    {
        return $this->hasOne(AvatarImage::class)
                    ->where('image_type', config('const.avatar_image_types.bust'))
                    ->where('expression_type', $expressionType);
    }
}