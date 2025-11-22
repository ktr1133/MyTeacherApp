<?php

namespace App\Services\Portal;

use App\Models\AppUpdate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * アプリ更新履歴サービスインターフェース
 */
interface AppUpdateServiceInterface
{
    /**
     * 更新履歴を取得
     *
     * @param string|null $appName
     * @param bool $majorOnly
     * @param int|null $limit
     * @return Collection
     */
    public function getUpdates(?string $appName = null, bool $majorOnly = false, ?int $limit = null): Collection;

    /**
     * 管理画面用：フィルター付きページネーション
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * IDで取得
     *
     * @param int $id
     * @return AppUpdate|null
     */
    public function findById(int $id): ?AppUpdate;

    /**
     * 更新履歴を作成
     *
     * @param array $data
     * @return AppUpdate
     */
    public function create(array $data): AppUpdate;

    /**
     * 更新履歴を更新
     *
     * @param AppUpdate $update
     * @param array $data
     * @return AppUpdate
     */
    public function update(AppUpdate $update, array $data): AppUpdate;

    /**
     * 更新履歴を削除
     *
     * @param AppUpdate $update
     * @return bool
     */
    public function delete(AppUpdate $update): bool;
}
