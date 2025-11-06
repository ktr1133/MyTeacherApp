<?php

namespace App\Repositories\Task;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection; // 追加
use App\Models\Task;

/**
 * TaskモデルとTagモデルのデータアクセス操作を定義するインターフェース。
 */
interface TaskRepositoryInterface
{
    /**
     * タスクを作成する。
     *
     * @param array $data タスクデータ配列
     * @return Task 作成されたタスクモデル
     */
    public function create(array $data): Task;

    /**
     * タスクを更新する（必要に応じてタグも同期）
     *
     * @param Task  $task
     * @param array $data
     * @return Task
     */
    public function update(Task $task, array $data): Task;

    /**
     * 指定されたユーザーIDに紐づくタスクのクエリビルダーを取得する。
     * ...
     */
    public function getTasksByUserId(int $userId): Builder;

    /**
     * データベースに登録されている全てのタグを取得する。
     *
     * @return Collection タグモデルのコレクション
     */
    public function getAllTags(): Collection;

    /**
     * タスクとタグの関連付けを同期する。タグ名が存在しない場合は新規作成する。
     * ...
     */
    public function syncTagsByName(Task $task, array $tagNames): void;

    /**
     * タイトルでタスクを検索
     *
     * @param int $userId
     * @param array $terms
     * @param string $operator 'and' or 'or'
     * @return array
     */
    public function searchByTitle(int $userId, array $terms, string $operator): array;

    /**
     * タグでタスクを検索
     *
     * @param int $userId
     * @param array $terms
     * @param string $operator 'and' or 'or'
     * @return array
     */
    public function searchByTags(int $userId, array $terms, string $operator): array;

    /**
     * タスクを完全に削除する
     *
     * @param Task $task
     * @return bool
     */
    public function deleteTask(Task $task): bool;

    /**
     * タスクからすべてのタグを解除する
     *
     * @param Task $task
     * @return void
     */
    public function detachAllTags(Task $task): void;

    /**
     * タスクを論理削除する（ソフトデリート）
     *
     * @param Task $task
     * @return bool
     */
    public function softDeleteTask(Task $task): bool;

    /**
     * 論理削除されたタスクを復元する
     *
     * @param Task $task
     * @return bool
     */
    public function restoreTask(Task $task): bool;

    /**
     * タスクを検索（ID指定）
     *
     * @param int $taskId
     * @return Task|null
     */
    public function findTaskById(int $taskId): ?Task;

    /**
     * 指定されたグループタスクIDに紐づくタスクを、特定のユーザーIDを除外して削除する
     *
     * @param string $groupTaskId
     * @param int $userId
     * @return int 削除したタスクの数
     */
    public function deleteByGroupTaskIdExcludingUser(string $groupTaskId, int $userId): int;

    /**
     * 指定されたグループタスクIDに紐づくタスクを復元する
     *
     * @param int $groupTaskId
     * @return int 復元したタスクの数
     */
    public function restoreByGroupTaskId(int $groupTaskId): int;
}