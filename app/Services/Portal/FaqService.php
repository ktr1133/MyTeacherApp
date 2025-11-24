<?php

namespace App\Services\Portal;

use App\Repositories\Portal\FaqRepositoryInterface;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * FAQサービス
 */
class FaqService implements FaqServiceInterface
{
    public function __construct(
        private FaqRepositoryInterface $repository
    ) {}

    /**
     * 公開されているFAQを全て取得
     *
     * @param string|null $category
     * @param string|null $appName
     * @return Collection
     */
    public function getAllPublished(?string $category = null, ?string $appName = null): Collection
    {
        $cacheKey = "portal.faqs." . ($category ?? 'all') . "." . ($appName ?? 'all');
        
        return Cache::remember($cacheKey, 3600, function () use ($category, $appName) {
            if ($category) {
                return $this->repository->getByCategory($category);
            }
            
            if ($appName) {
                return $this->repository->getByApp($appName);
            }
            
            return $this->repository->getAllPublished();
        });
    }

    /**
     * FAQを検索
     *
     * @param string $keyword
     * @return Collection
     */
    public function search(string $keyword): Collection
    {
        return $this->repository->search($keyword);
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
        return $this->repository->paginateWithFilters($filters, $perPage);
    }

    /**
     * IDで取得
     *
     * @param int $id
     * @return Faq|null
     */
    public function findById(int $id): ?Faq
    {
        return $this->repository->findById($id);
    }

    /**
     * FAQを作成
     *
     * @param array $data
     * @return Faq
     */
    public function create(array $data): Faq
    {
        $faq = $this->repository->create($data);
        $this->clearCache();
        return $faq;
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
        $faq = $this->repository->update($faq, $data);
        $this->clearCache();
        return $faq;
    }

    /**
     * FAQを削除
     *
     * @param Faq $faq
     * @return bool
     */
    public function delete(Faq $faq): bool
    {
        $result = $this->repository->delete($faq);
        $this->clearCache();
        return $result;
    }

    /**
     * 公開状態を切り替え
     *
     * @param Faq $faq
     * @return Faq
     */
    public function togglePublished(Faq $faq): Faq
    {
        $faq = $this->repository->togglePublished($faq);
        $this->clearCache();
        return $faq;
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
        $faq = $this->repository->updateDisplayOrder($faq, $displayOrder);
        $this->clearCache();
        return $faq;
    }

    /**
     * キャッシュをクリア
     *
     * @return void
     */
    private function clearCache(): void
    {
        Cache::flush(); // 簡易的に全キャッシュクリア（本番環境では個別削除推奨）
    }
}
