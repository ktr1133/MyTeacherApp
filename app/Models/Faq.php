<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * よくある質問モデル
 */
class Faq extends Model
{
    /**
     * 複数代入可能な属性
     *
     * @var array<string>
     */
    protected $fillable = [
        'category',
        'app_name',
        'question',
        'answer',
        'display_order',
        'is_published',
    ];

    /**
     * キャストする属性
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_published' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * 公開されているFAQのスコープ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * カテゴリでフィルタリング
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $category
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

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
     * 表示順でソート
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc');
    }
}
