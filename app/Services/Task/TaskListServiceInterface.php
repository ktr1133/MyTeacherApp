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

    /**
     * 無限スクロール用にページネーションされたタスク一覧を取得
     *
     * @param int $userId タスクを取得するユーザーのID
     * @param array $filters 適用するフィルタパラメータ
     * @param int $page ページ番号（1から開始）
     * @param int $perPage 1ページあたりの取得件数
     * @return array ['tasks' => Collection, 'has_more' => bool, 'next_page' => int]
     */
    public function getTasksForUserPaginated(int $userId, array $filters, int $page = 1, int $perPage = 50): array;
}