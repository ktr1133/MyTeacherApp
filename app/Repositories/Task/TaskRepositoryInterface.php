<?php

namespace App\Repositories\Task;

use App\Models\TaskProposal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
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
     * タスク提案を作成する
     *
     * @param int $userId
     * @param string $originalTaskText
     * @param string $context
     * @param array $aiResponse
     * @return TaskProposal
     */
    public function createProposal(int $userId, string $originalTaskText, string $context, array $aiResponse): TaskProposal;

    /**
     * AIが提案したタスクを作成する
     *
     * @param int $userId
     * @param int $proposalId
     * @param array $proposedTasks
     * @return SupportCollection
     */
    public function createTasksFromProposal(int $userId, int $proposalId, array $proposedTasks): SupportCollection;

    /**
     * タイトルでタスクを検索
     *
     * @param int $userId
     * @param array $terms
     * @param string $operator 'and' or 'or'
     * @return Collection
     */
    public function searchByTitle(int $userId, array $terms, string $operator): Collection;

    /**
     * タグでタスクを検索
     *
     * @param int $userId
     * @param array $terms
     * @param string $operator 'and' or 'or'
     * @return Collection
     */
    public function searchByTags(int $userId, array $terms, string $operator): Collection;

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
     * @param string $groupTaskId
     * @return int 復元したタスクの数
     */
    public function restoreByGroupTaskId(string $groupTaskId): int;

    /**
     * タスクにタグを紐付け（Batch用）
     *
     * @param int $taskId
     * @param array $tagNames
     * @return void
     */
    public function attachTagsForBatch(int $taskId, array $tagNames): void;

    /**
     * グループのメンバーIDを取得（Batch用）
     *
     * @param int $groupId
     * @return array
     */
    public function getGroupMemberIds(int $groupId): array;

    /**
     * タスクを論理削除（Batch用）
     *
     * @param int $taskId
     * @return bool
     */
    public function softDeleteById(int $taskId): bool;

    /**
     * 指定されたgroup_task_idを持つすべてのタスクを取得
     *
     * @param string $groupTaskId
     * @return Collection
     */
    public function findTasksByGroupTaskId(string $groupTaskId): Collection;

    /**
     * ユーザーが作成した編集可能なグループタスクを取得（group_task_id単位でグループ化）
     *
     * 条件:
     * - group_task_id IS NOT NULL
     * - assigned_by_user_id = $userId
     * - approved_at IS NULL
     * - deleted_at IS NULL
     *
     * @param int $userId
     * @return SupportCollection groupBy()->map()の結果を返すためSupportCollection
     */
    public function findEditableGroupTasksByUser(int $userId): SupportCollection;    /**
     * 特定のgroup_task_idのタスクを取得（編集可能なもののみ）
     *
     * @param string $groupTaskId
     * @param int $assignedByUserId
     * @return Collection
     */
    public function findEditableTasksByGroupTaskId(string $groupTaskId, int $assignedByUserId): Collection;

    /**
     * グループタスクを一括更新（同じgroup_task_idのタスク全体）
     *
     * @param string $groupTaskId
     * @param int $assignedByUserId
     * @param array $data
     * @return int 更新されたタスク数
     */
    public function updateTasksByGroupTaskId(string $groupTaskId, int $assignedByUserId, array $data): int;

    /**
     * グループタスクを一括論理削除（同じgroup_task_idのタスク全体）
     *
     * @param string $groupTaskId
     * @param int $assignedByUserId
     * @return int 削除されたタスク数
     */
    public function softDeleteTasksByGroupTaskId(string $groupTaskId, int $assignedByUserId): int;
}