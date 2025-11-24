<?php

namespace App\Services\Portal;

use App\Models\AppUpdate;
use App\Repositories\Portal\AppUpdateRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * アプリ更新履歴サービス
 */
class AppUpdateService implements AppUpdateServiceInterface
{
    public function __construct(
        private AppUpdateRepositoryInterface $repository
    ) {}

    /**
     * 更新履歴を取得
     *
     * @param string|null $appName
     * @param bool $majorOnly
     * @param int|null $limit
     * @return Collection
     */
    public function getUpdates(?string $appName = null, bool $majorOnly = false, ?int $limit = null): Collection
    {
        $cacheKey = "portal.updates." . ($appName ?? 'all') . "." . ($majorOnly ? 'major' : 'all') . "." . ($limit ?? 'all');
        
        return Cache::remember($cacheKey, 3600, function () use ($appName, $majorOnly, $limit) {
            if ($majorOnly) {
                return $this->repository->getMajorUpdates($appName, $limit);
            }
            
            if ($appName) {
                return $this->repository->getByApp($appName, $limit);
            }
            
            return $this->repository->getAll($limit);
        });
    }

    /**
     * 管理画面用:フィルター付きページネーション
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateWithFilters($filters, $perPage);
    }

    /**
     * IDで取得
     *
     * @param int $id
     * @return AppUpdate|null
     */
    public function findById(int $id): ?AppUpdate
    {
        return $this->repository->findById($id);
    }

    /**
     * 更新履歴を作成
     *
     * @param array $data
     * @return AppUpdate
     */
    public function create(array $data): AppUpdate
    {
        $update = $this->repository->create($data);
        $this->clearCache();
        return $update;
    }

    /**
     * 更新履歴を更新
     *
     * @param AppUpdate $update
     * @param array $data
     * @return AppUpdate
     */
    public function update(AppUpdate $update, array $data): AppUpdate
    {
        $update = $this->repository->update($update, $data);
        $this->clearCache();
        return $update;
    }

    /**
     * 更新履歴を削除
     *
     * @param AppUpdate $update
     * @return bool
     */
    public function delete(AppUpdate $update): bool
    {
        $result = $this->repository->delete($update);
        $this->clearCache();
        return $result;
    }

    /**
     * キャッシュをクリア
     *
     * @return void
     */
    private function clearCache(): void
    {
        Cache::flush();
    }
}
