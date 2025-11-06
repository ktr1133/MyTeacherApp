<?php

namespace App\Services\Task;

interface TaskSearchServiceInterface
{
    /**
     * タスクを検索する
     *
     * @param int $userId
     * @param string $type 'title' or 'tag'
     * @param string $operator 'and' or 'or'
     * @param array $terms 検索語の配列
     * @return array
     */
    public function search(int $userId, string $type, string $operator, array $terms): array;
}