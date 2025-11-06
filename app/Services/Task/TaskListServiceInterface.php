<?php

namespace App\Services\Task;

use Illuminate\Support\Collection;

/**
 * タスク一覧画面に関するビジネスロジックの契約を定義するインターフェース。
 */
interface TaskListServiceInterface
{
    /**
     * 認証済みユーザーのタスクリストを取得し、指定されたフィルタを適用する。
     *
     * @param int $userId タスクを取得するユーザーのID
     * @param array $filters 適用するフィルタパラメータ（search, status, priority, tagsなど）
     * @return Collection フィルタリングされ、ソートされたタスクのコレクション
     */
    public function getTasksForUser(int $userId, array $filters): Collection;
}