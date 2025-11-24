<?php

namespace App\Services\Portal;

use App\Models\Maintenance;
use App\Repositories\Portal\MaintenanceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * メンテナンス情報サービス
 */
class MaintenanceService implements MaintenanceServiceInterface
{
    public function __construct(
        private MaintenanceRepositoryInterface $repository
    ) {}

    /**
     * 予定されているメンテナンス情報を取得
     *
     * @param int|null $limit
     * @return Collection
     */
    public function getUpcoming(?int $limit = null): Collection
    {
        $cacheKey = "portal.maintenances.upcoming." . ($limit ?? 'all');
        
        return Cache::remember($cacheKey, 300, function () use ($limit) {
            return $this->repository->getUpcoming($limit);
        });
    }

    /**
     * 全てのメンテナンス情報を取得
     *
     * @param string|null $status
     * @param string|null $appName
     * @return Collection
     */
    public function getAll(?string $status = null, ?string $appName = null): Collection
    {
        if ($status) {
            return $this->repository->getByStatus($status);
        }
        
        if ($appName) {
            return $this->repository->getByApp($appName);
        }
        
        return $this->repository->getAll();
    }

    /**
     * メンテナンス情報を作成
     *
     * @param array $data
     * @return Maintenance
     */
    public function create(array $data): Maintenance
    {
        $maintenance = $this->repository->create($data);
        
        $this->clearCache();
        
        return $maintenance;
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
        $maintenance = $this->repository->update($maintenance, $data);
        
        $this->clearCache();
        
        return $maintenance;
    }

    /**
     * メンテナンス情報を削除
     *
     * @param Maintenance $maintenance
     * @return bool
     */
    public function delete(Maintenance $maintenance): bool
    {
        $result = $this->repository->delete($maintenance);
        
        $this->clearCache();
        
        return $result;
    }

    /**
     * メンテナンスを開始
     *
     * @param Maintenance $maintenance
     * @return Maintenance
     */
    public function start(Maintenance $maintenance): Maintenance
    {
        return $this->update($maintenance, [
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    /**
     * メンテナンスを完了
     *
     * @param Maintenance $maintenance
     * @return Maintenance
     */
    public function complete(Maintenance $maintenance): Maintenance
    {
        return $this->update($maintenance, [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * 管理画面用：ページネーション付きで取得
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(array $filters = [], int $perPage = 15)
    {
        return $this->repository->paginate($filters, $perPage);
    }

    /**
     * IDでメンテナンス情報を取得
     *
     * @param int $id
     * @return Maintenance|null
     */
    public function findById(int $id): ?Maintenance
    {
        return $this->repository->findById($id);
    }

    /**
     * キャッシュをクリア
     *
     * @return void
     */
    private function clearCache(): void
    {
        Cache::forget('portal.maintenances.upcoming.3');
        Cache::forget('portal.maintenances.upcoming.all');
    }
}
