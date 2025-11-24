<?php

namespace App\Repositories\Portal;

use App\Models\AppUpdate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * アプリ更新履歴リポジトリインターフェース
 */
interface AppUpdateRepositoryInterface
{
    /**
     * 全ての更新履歴を取得
     *
     * @param int|null $limit
     * @return Collection
     */
    public function getAll(?int $limit = null): Collection;

    /**
     * IDで更新履歴を取得
     *
     * @param int $id
     * @return AppUpdate|null
     */
    public function findById(int $id): ?AppUpdate;

    /**
     * アプリでフィルタリング
     *
     * @param string $appName
     * @param int|null $limit
     * @return Collection
     */
    public function getByApp(string $appName, ?int $limit = null): Collection;

    /**
     * メジャーアップデートのみ取得
     *
     * @param string|null $appName
     * @param int|null $limit
     * @return Collection
     */
    public function getMajorUpdates(?string $appName = null, ?int $limit = null): Collection;

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

    /**
     * 管理画面用：フィルター付きページネーション
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator;
}
