<?php

namespace App\Repositories\Portal;

use App\Models\Maintenance;
use Illuminate\Database\Eloquent\Collection;

/**
 * メンテナンス情報リポジトリインターフェース
 */
interface MaintenanceRepositoryInterface
{
    /**
     * 全てのメンテナンス情報を取得
     *
     * @return Collection
     */
    public function getAll(): Collection;

    /**
     * IDでメンテナンス情報を取得
     *
     * @param int $id
     * @return Maintenance|null
     */
    public function findById(int $id): ?Maintenance;

    /**
     * 予定メンテナンスを取得
     *
     * @param int|null $limit
     * @return Collection
     */
    public function getUpcoming(?int $limit = null): Collection;

    /**
     * ステータスでフィルタリング
     *
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $status): Collection;

    /**
     * アプリでフィルタリング
     *
     * @param string $appName
     * @return Collection
     */
    public function getByApp(string $appName): Collection;

    /**
     * メンテナンス情報を作成
     *
     * @param array $data
     * @return Maintenance
     */
    public function create(array $data): Maintenance;

    /**
     * メンテナンス情報を更新
     *
     * @param Maintenance $maintenance
     * @param array $data
     * @return Maintenance
     */
    public function update(Maintenance $maintenance, array $data): Maintenance;

    /**
     * メンテナンス情報を削除
     *
     * @param Maintenance $maintenance
     * @return bool
     */
    public function delete(Maintenance $maintenance): bool;

    /**
     * 管理画面用：ページネーション付きで取得
     *
     * @param array $filters ステータス、アプリなどのフィルター
     * @param int $perPage ページあたりの件数
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(array $filters = [], int $perPage = 15);

    /**
     * ステータスを更新
     *
     * @param Maintenance $maintenance
     * @param string $status
     * @return Maintenance
     */
    public function updateStatus(Maintenance $maintenance, string $status): Maintenance;
}
