<?php

namespace App\Repositories\Portal;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Collection;

/**
 * FAQEloquentリポジトリ
 */
class EloquentFaqRepository implements FaqRepositoryInterface
{
    /**
     * 公開されているFAQを全て取得
     *
     * @return Collection
     */
    public function getAllPublished(): Collection
    {
        return Faq::published()->ordered()->get();
    }

    /**
     * IDでFAQを取得
     *
     * @param int $id
     * @return Faq|null
     */
    public function findById(int $id): ?Faq
    {
        return Faq::find($id);
    }

    /**
     * カテゴリでフィルタリング
     *
     * @param string $category
     * @param bool $publishedOnly
     * @return Collection
     */
    public function getByCategory(string $category, bool $publishedOnly = true): Collection
    {
        $query = Faq::byCategory($category)->ordered();
        
        if ($publishedOnly) {
            $query->published();
        }
        
        return $query->get();
    }

    /**
     * アプリでフィルタリング
     *
     * @param string $appName
     * @param bool $publishedOnly
     * @return Collection
     */
    public function getByApp(string $appName, bool $publishedOnly = true): Collection
    {
        $query = Faq::forApp($appName)->ordered();
        
        if ($publishedOnly) {
            $query->published();
        }
        
        return $query->get();
    }

    /**
     * キーワード検索
     *
     * @param string $keyword
     * @param bool $publishedOnly
     * @return Collection
     */
    public function search(string $keyword, bool $publishedOnly = true): Collection
    {
        // 空文字列の場合は空のコレクションを返す
        if (empty(trim($keyword))) {
            return new Collection();
        }
        
        $query = Faq::where(function ($q) use ($keyword) {
            $q->where('question', 'LIKE', "%{$keyword}%")
              ->orWhere('answer', 'LIKE', "%{$keyword}%");
        })->ordered();
        
        if ($publishedOnly) {
            $query->published();
        }
        
        return $query->get();
    }

    /**
     * FAQを作成
     *
     * @param array $data
     * @return Faq
     */
    public function create(array $data): Faq
    {
        return Faq::create($data);
    }

    /**
     * FAQを更新
     *
     * @param Faq $faq
     * @param array $data
     * @return Faq
     */
    public function update(Faq $faq, array $data): Faq
    {
        $faq->update($data);
        return $faq->fresh();
    }

    /**
     * FAQを削除
     *
     * @param Faq $faq
     * @return bool
     */
    public function delete(Faq $faq): bool
    {
        return $faq->delete();
    }

    /**
     * 管理画面用：フィルター付きページネーション
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 15)
    {
        $query = Faq::query();

        // カテゴリフィルター
        if (!empty($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        // アプリフィルター
        if (!empty($filters['app_name'])) {
            $query->forApp($filters['app_name']);
        }

        // 公開状態フィルター
        if (isset($filters['is_published'])) {
            $query->where('is_published', $filters['is_published']);
        }

        return $query->ordered()->paginate($perPage);
    }

    /**
     * 表示順を更新
     *
     * @param Faq $faq
     * @param int $displayOrder
     * @return Faq
     */
    public function updateDisplayOrder(Faq $faq, int $displayOrder): Faq
    {
        $faq->update(['display_order' => $displayOrder]);
        return $faq->fresh();
    }

    /**
     * 公開状態を切り替え
     *
     * @param Faq $faq
     * @return Faq
     */
    public function togglePublished(Faq $faq): Faq
    {
        $faq->update(['is_published' => !$faq->is_published]);
        return $faq->fresh();
    }

    /**
     * 全てのFAQを取得（管理画面用、非公開含む）
     *
     * @return Collection
     */
    public function getAll(): Collection
    {
        return Faq::ordered()->get();
    }
}
