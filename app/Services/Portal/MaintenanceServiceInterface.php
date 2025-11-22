<?php

namespace App\Services\Portal;

use App\Models\Maintenance;
use Illuminate\Database\Eloquent\Collection;

/**
 * メンテナンス情報サービスインターフェース
 */
interface MaintenanceServiceInterface
{
    /**
     * 予定されているメンテナンス情報を取得
     *
     * @param int|null $limit
     * @return Collection
     */
    public function getUpcoming(?int $limit = null): Collection;

    /**
     * 全てのメンテナンス情報を取得
     *
     * @param string|null $status
     * @param string|null $appName
     * @return Collection
     */
    public function getAll(?string $status = null, ?string $appName = null): Collection;

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
     * メンテナンスを開始
     *
     * @param Maintenance $maintenance
     * @return Maintenance
     */
    public function start(Maintenance $maintenance): Maintenance;

    /**
     * メンテナンスを完了
     *
     * @param Maintenance $maintenance
     * @return Maintenance
     */
    public function complete(Maintenance $maintenance): Maintenance;

    /**
     * 管理画面用：ページネーション付きで取得
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(array $filters = [], int $perPage = 15);

    /**
     * IDでメンテナンス情報を取得
     *
     * @param int $id
     * @return Maintenance|null
     */
    public function findById(int $id): ?Maintenance;
}
