<?php

namespace App\Services\Task;

use App\Repositories\Task\TaskRepositoryInterface;

class TaskSearchService implements TaskSearchServiceInterface
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository
    ) {}

    /**
     * タスクを検索する
     *
     * @param int $userId
     * @param string $type 'title' or 'tag'
     * @param string $operator 'and' or 'or'
     * @param array $terms 検索語の配列
     * @return array
     */
    public function search(int $userId, string $type, string $operator, array $terms): array
    {
        if ($type === 'tag') {
            return $this->taskRepository->searchByTags($userId, $terms, $operator);
        }

        return $this->taskRepository->searchByTitle($userId, $terms, $operator);
    }
}