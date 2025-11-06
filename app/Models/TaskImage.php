<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TaskImage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'task_id',
        'file_path',
        'approved_at',
        'delete_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'approved_at' => 'datetime',
        'delete_at' => 'datetime',
    ];

    /**
     * この画像が属するタスクを取得する。
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * 画像のフルURLを取得する。
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * 承認処理を実行し、削除予定日時を設定する。
     */
    public function approve(): void
    {
        $this->approved_at = now();
        $this->delete_at = now()->addDays(3);
        $this->save();
    }

    /**
     * 削除予定日時を過ぎているかどうかを確認する。
     */
    public function shouldBeDeleted(): bool
    {
        return $this->delete_at && now()->greaterThan($this->delete_at);
    }
}