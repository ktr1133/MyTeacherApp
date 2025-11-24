<?php

namespace App\Services\Portal;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Collection;

/**
 * FAQサービスインターフェース
 */
interface FaqServiceInterface
{
    /**
     * 公開されているFAQを全て取得
     *
     * @param string|null $category
     * @param string|null $appName
     * @return Collection
     */
    public function getAllPublished(?string $category = null, ?string $appName = null): Collection;

    /**
     * FAQを検索
     *
     * @param string $keyword
     * @return Collection
     */
    public function search(string $keyword): Collection;

    /**
     * 管理画面用：フィルター付きページネーション
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 15);

    /**
     * IDで取得
     *
     * @param int $id
     * @return Faq|null
     */
    public function findById(int $id): ?Faq;

    /**
     * FAQを作成
     *
     * @param array $data
     * @return Faq
     */
    public function create(array $data): Faq;

    /**
     * FAQを更新
     *
     * @param Faq $faq
     * @param array $data
     * @return Faq
     */
    public function update(Faq $faq, array $data): Faq;

    /**
     * FAQを削除
     *
     * @param Faq $faq
     * @return bool
     */
    public function delete(Faq $faq): bool;

    /**
     * 公開状態を切り替え
     *
     * @param Faq $faq
     * @return Faq
     */
    public function togglePublished(Faq $faq): Faq;

    /**
     * 表示順を更新
     *
     * @param Faq $faq
     * @param int $displayOrder
     * @return Faq
     */
    public function updateDisplayOrder(Faq $faq, int $displayOrder): Faq;
}
