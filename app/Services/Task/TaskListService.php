<?php

namespace App\Services\Task;

use App\Repositories\Task\TaskRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * TaskListServiceInterface の具象クラス。
 * * タスクのフィルタリング、ソート、検索ロジックを実装する。
 */
class TaskListService implements TaskListServiceInterface // ★ インターフェースを実装
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
     * @inheritDoc
     */
    public function getTasksForUser(int $userId, array $filters): Collection
    {
        // Repository経由でタスクのクエリビルダーを取得
        $query = $this->taskRepository->getTasksByUserId($userId);

        // ... フィルタリングロジックの適用 ...
        
        return $query->orderBy('due_date')->orderBy('priority')->get();
    }
}