<?php

namespace App\Services\Task;

use App\Repositories\Task\TaskRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * TaskListServiceInterface の具象クラス。
 * タスクのフィルタリング、ソート、検索ロジックを実装する。
 */
class TaskListService implements TaskListServiceInterface
{
    protected TaskRepositoryInterface $taskRepository;

    /**
     * コンストラクタ。リポジトリの依存性を注入。
     *
     * @param TaskRepositoryInterface $taskRepository データアクセス層のインターフェース
     */
    public function __construct(TaskRepositoryInterface $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    /**
     * ユーザーのタスク一覧を取得（キャッシュ付き）
     *
     * @param int $userId ユーザーID
     * @param array $filters フィルター条件
     * @return Collection タスクコレクション
     */
    public function getTasksForUser(int $userId, array $filters): Collection
    {
        try {
            // フィルター適用時はキャッシュをバイパス
            if ($this->hasActiveFilters($filters)) {
                return $this->fetchTasksFromDatabase($userId, $filters);
            }
            
            $cacheKey = "dashboard:user:{$userId}:tasks";
            
            return Cache::tags(['dashboard', "user:{$userId}"])->remember(
                $cacheKey,
                now()->addMinutes(5),
                fn() => $this->fetchTasksFromDatabase($userId, $filters)
            );
            
        } catch (Exception $e) {
            Log::warning('Cache unavailable, falling back to database', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return $this->fetchTasksFromDatabase($userId, $filters);
        }
    }

    /**
     * データベースからタスクを取得
     *
     * @param int $userId ユーザーID
     * @param array $filters フィルター条件
     * @return Collection タスクコレクション
     */
    private function fetchTasksFromDatabase(int $userId, array $filters): Collection
    {
        // Repository経由でタスクのクエリビルダーを取得
        $query = $this->taskRepository->getTasksByUserId($userId);

        // ... フィルタリングロジックの適用 ...
        
        return $query->orderBy('due_date')->orderBy('priority')->get();
    }

    /**
     * アクティブなフィルターが存在するかチェック
     *
     * @param array $filters フィルター条件
     * @return bool フィルターが存在する場合true
     */
    private function hasActiveFilters(array $filters): bool
    {
        return !empty($filters['search']) || 
               !empty($filters['status']) || 
               !empty($filters['priority']) || 
               !empty($filters['tags']);
    }
}