<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * アプリ更新履歴モデル
 */
class AppUpdate extends Model
{
    /**
     * 複数代入可能な属性
     *
     * @var array<string>
     */
    protected $fillable = [
        'app_name',
        'version',
        'title',
        'description',
        'changes',
        'released_at',
        'is_major',
    ];

    /**
     * キャストする属性
     *
     * @var array<string, string>
     */
    protected $casts = [
        'released_at' => 'datetime',
        'is_major' => 'boolean',
        'changes' => 'array',
    ];

    /**
     * アプリでフィルタリング
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $appName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForApp($query, string $appName)
    {
        return $query->where('app_name', $appName);
    }

    /**
     * メジャーアップデートのみ取得
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMajorOnly($query)
    {
        return $query->where('is_major', true);
    }

    /**
     * リリース日降順でソート
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('released_at', 'desc');
    }
}
