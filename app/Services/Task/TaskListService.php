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
     * ユーザーの未完了タスク一覧を取得（キャッシュ付き）
     *
     * @param int $userId ユーザーID
     * @param array $filters フィルター条件
     * @return Collection タスクコレクション（未完了のみ）
     */
    public function getTasksForUser(int $userId, array $filters): Collection
    {
        try {
            // フィルター適用時はキャッシュをバイパス
            if ($this->hasActiveFilters($filters)) {
                return $this->fetchTasksFromDatabase($userId, $filters);
            }
            
            $cacheKey = "dashboard:user:{$userId}:incomplete-tasks";
            
            return Cache::tags(['dashboard', "user:{$userId}"])->remember(
                $cacheKey,
                now()->addMinutes(15),
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
     * データベースから未完了タスクを取得
     *
     * @param int $userId ユーザーID
     * @param array $filters フィルター条件
     * @return Collection タスクコレクション（未完了のみ）
     */
    private function fetchTasksFromDatabase(int $userId, array $filters): Collection
    {
        // Repository経由でタスクのクエリビルダーを取得
        $query = $this->taskRepository->getTasksByUserId($userId);

        // 未完了タスクのみに絞り込む
        $query->where('is_completed', false);

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

    /**
     * 無限スクロール用にページネーションされたタスク一覧を取得
     *
     * @param int $userId ユーザーID
     * @param array $filters フィルター条件
     * @param int $page ページ番号（1から開始）
     * @param int $perPage 1ページあたりの取得件数
     * @return array ['tasks' => Collection, 'has_more' => bool, 'next_page' => int]
     */
    public function getTasksForUserPaginated(int $userId, array $filters, int $page = 1, int $perPage = 50): array
    {
        try {
            // キャッシュはページごとに管理
            $cacheKey = "dashboard:user:{$userId}:tasks:page:{$page}:perpage:{$perPage}";
            
            // フィルター適用時はキャッシュをバイパス
            if ($this->hasActiveFilters($filters)) {
                return $this->fetchTasksFromDatabasePaginated($userId, $filters, $page, $perPage);
            }
            
            return Cache::tags(['dashboard', "user:{$userId}"])->remember(
                $cacheKey,
                now()->addMinutes(15),
                fn() => $this->fetchTasksFromDatabasePaginated($userId, $filters, $page, $perPage)
            );
            
        } catch (Exception $e) {
            Log::warning('Cache unavailable for paginated tasks, falling back to database', [
                'user_id' => $userId,
                'page' => $page,
                'error' => $e->getMessage()
            ]);
            
            return $this->fetchTasksFromDatabasePaginated($userId, $filters, $page, $perPage);
        }
    }

    /**
     * データベースからページネーションされたタスクを取得
     *
     * @param int $userId ユーザーID
     * @param array $filters フィルター条件
     * @param int $page ページ番号
     * @param int $perPage 1ページあたりの件数
     * @return array ['tasks' => Collection, 'has_more' => bool, 'next_page' => int]
     */
    private function fetchTasksFromDatabasePaginated(int $userId, array $filters, int $page, int $perPage): array
    {
        // Repository経由でタスクのクエリビルダーを取得
        $query = $this->taskRepository->getTasksByUserId($userId);

        // 未完了タスクのみに絞り込む
        $query->where('is_completed', false);

        // フィルタリング適用（既存ロジックと同様）
        if (!empty($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }
        
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        
        if (!empty($filters['tags'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->whereIn('tags.id', (array)$filters['tags']);
            });
        }

        // ソート
        $query->orderBy('due_date')->orderBy('priority');
        
        // ページネーション適用
        $offset = ($page - 1) * $perPage;
        $tasks = $query->skip($offset)->take($perPage + 1)->get();
        
        // 次のページがあるかチェック（$perPage + 1件取得して判定）
        $hasMore = $tasks->count() > $perPage;
        
        // 実際に返すのは$perPage件のみ
        if ($hasMore) {
            $tasks = $tasks->take($perPage);
        }
        
        return [
            'tasks' => $tasks,
            'has_more' => $hasMore,
            'next_page' => $page + 1,
            'current_page' => $page,
        ];
    }
}