<?php

namespace App\Repositories\Portal;

use App\Models\AppUpdate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * アプリ更新履歴Eloquentリポジトリ
 */
class EloquentAppUpdateRepository implements AppUpdateRepositoryInterface
{
    /**
     * 全ての更新履歴を取得
     *
     * @param int|null $limit
     * @return Collection
     */
    public function getAll(?int $limit = null): Collection
    {
        $query = AppUpdate::latest();
        
        if ($limit !== null) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    /**
     * IDで更新履歴を取得
     *
     * @param int $id
     * @return AppUpdate|null
     */
    public function findById(int $id): ?AppUpdate
    {
        return AppUpdate::find($id);
    }

    /**
     * アプリでフィルタリング
     *
     * @param string $appName
     * @param int|null $limit
     * @return Collection
     */
    public function getByApp(string $appName, ?int $limit = null): Collection
    {
        $query = AppUpdate::forApp($appName)->latest();
        
        if ($limit !== null) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    /**
     * メジャーアップデートのみ取得
     *
     * @param string|null $appName
     * @param int|null $limit
     * @return Collection
     */
    public function getMajorUpdates(?string $appName = null, ?int $limit = null): Collection
    {
        $query = AppUpdate::majorOnly()->latest();
        
        if ($appName !== null) {
            $query->forApp($appName);
        }
        
        if ($limit !== null) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    /**
     * 更新履歴を作成
     *
     * @param array $data
     * @return AppUpdate
     */
    public function create(array $data): AppUpdate
    {
        return AppUpdate::create($data);
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
        $update->update($data);
        return $update->fresh();
    }

    /**
     * 更新履歴を削除
     *
     * @param AppUpdate $update
     * @return bool
     */
    public function delete(AppUpdate $update): bool
    {
        return $update->delete();
    }

    /**
     * 管理画面用：フィルター付きページネーション
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = AppUpdate::query()->latest();

        // アプリ名フィルター
        if (!empty($filters['app_name'])) {
            $query->forApp($filters['app_name']);
        }

        // メジャーアップデートフィルター
        if (isset($filters['is_major']) && $filters['is_major'] !== '') {
            if ($filters['is_major'] === '1') {
                $query->majorOnly();
            } else {
                $query->where('is_major', false);
            }
        }

        // リリース日フィルター（開始）
        if (!empty($filters['released_from'])) {
            $query->where('release_date', '>=', $filters['released_from']);
        }

        // リリース日フィルター（終了）
        if (!empty($filters['released_to'])) {
            $query->where('release_date', '<=', $filters['released_to']);
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
