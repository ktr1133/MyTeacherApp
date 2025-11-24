<?php

namespace App\Repositories\Portal;

use App\Models\Maintenance;
use Illuminate\Database\Eloquent\Collection;

/**
 * メンテナンス情報Eloquentリポジトリ
 */
class EloquentMaintenanceRepository implements MaintenanceRepositoryInterface
{
    /**
     * 全てのメンテナンス情報を取得
     *
     * @return Collection
     */
    public function getAll(): Collection
    {
        return Maintenance::with('creator')
            ->orderBy('scheduled_at', 'desc')
            ->get();
    }

    /**
     * IDでメンテナンス情報を取得
     *
     * @param int $id
     * @return Maintenance|null
     */
    public function findById(int $id): ?Maintenance
    {
        return Maintenance::with('creator')->find($id);
    }

    /**
     * 予定メンテナンスを取得
     *
     * @param int|null $limit
     * @return Collection
     */
    public function getUpcoming(?int $limit = null): Collection
    {
        $query = Maintenance::upcoming()->with('creator');
        
        if ($limit !== null) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    /**
     * ステータスでフィルタリング
     *
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $status): Collection
    {
        return Maintenance::where('status', $status)
            ->with('creator')
            ->orderBy('scheduled_at', 'desc')
            ->get();
    }

    /**
     * アプリでフィルタリング
     *
     * @param string $appName
     * @return Collection
     */
    public function getByApp(string $appName): Collection
    {
        return Maintenance::whereJsonContains('affected_apps', $appName)
            ->with('creator')
            ->orderBy('scheduled_at', 'desc')
            ->get();
    }

    /**
     * メンテナンス情報を作成
     *
     * @param array $data
     * @return Maintenance
     */
    public function create(array $data): Maintenance
    {
        return Maintenance::create($data);
    }

    /**
     * メンテナンス情報を更新
     *
     * @param Maintenance $maintenance
     * @param array $data
     * @return Maintenance
     */
    public function update(Maintenance $maintenance, array $data): Maintenance
    {
        $maintenance->update($data);
        return $maintenance->fresh();
    }

    /**
     * メンテナンス情報を削除
     *
     * @param Maintenance $maintenance
     * @return bool
     */
    public function delete(Maintenance $maintenance): bool
    {
        return $maintenance->delete();
    }

    /**
     * 管理画面用：ページネーション付きで取得
     *
     * @param array $filters ステータス、アプリなどのフィルター
     * @param int $perPage ページあたりの件数
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(array $filters = [], int $perPage = 15)
    {
        $query = Maintenance::with('creator');

        // ステータスフィルター
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // アプリフィルター
        if (!empty($filters['app_name'])) {
            $query->whereJsonContains('affected_apps', $filters['app_name']);
        }

        return $query->orderBy('scheduled_at', 'desc')->paginate($perPage);
    }

    /**
     * ステータスを更新
     *
     * @param Maintenance $maintenance
     * @param string $status
     * @return Maintenance
     */
    public function updateStatus(Maintenance $maintenance, string $status): Maintenance
    {
        $maintenance->update(['status' => $status]);
        return $maintenance->fresh();
    }
}
