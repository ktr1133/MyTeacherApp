<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Tag extends Model
{
    use HasFactory;

    /**
     * マス割当て可能な属性
     *
     * @var array<int,string>
     */
    protected $fillable = [
        'user_id',
        'name',
    ];

    /**
     * タグが紐づいているタスクを取得する (多対多)。
     * 中間テーブル名は実際のテーブル 'task_tag' を指定する。
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_tag');
    }

    /**
     * 名前で検索する簡易スコープ（部分一致）
     *
     * @param Builder $query
     * @param string|null $term
     * @return Builder
     */
    public function scopeSearchByName(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where('name', 'ilike', "%{$term}%");
    }

    /**
     * タグ名から取得または作成してID配列を返すユーティリティ
     *
     * @param array<string> $names
     * @return array<int>
     */
    public static function findOrCreateIdsByName(array $names): array
    {
        $ids = [];
        foreach ($names as $n) {
            $n = trim($n);
            if ($n === '') {
                continue;
            }
            $tag = static::firstOrCreate(['name' => $n]);
            $ids[] = $tag->id;
        }
        return $ids;
    }
}