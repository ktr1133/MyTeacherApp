<?php

namespace App\Services\Task;

use App\Models\Task;
use App\Models\User;

/**
 * タスクの作成、更新、AI連携、論理削除などのCRUD操作とビジネスロジックの契約を定義するインターフェース。
 */
interface TaskManagementServiceInterface
{
    /**
     * IDでタスクを検索する。
     *
     * @param int $id タスクID
     * @return Task|null 見つかったタスクモデル、またはnull
     */
    public function findById(int $id): ?Task;

    /**
     * 新しいタスクをデータベースに保存し、タグを関連付ける。
     *
     * @param User $user タスクを作成するユーザー
     * @param array $data タスクデータ（title, description, due_date, priority, tagsなど）
     * @param bool $groupFlg グループタスクフラグ
     * @return Task 保存されたタスクモデル
     */
    public function createTask(User $user, array $data, bool $groupFlg): Task;

    /**
     * タスクを更新する
     *
     * @param Task $task
     * @param array $data ['title', 'description', 'span', 'due_date', 'tags']
     * @return Task
     * @throws \Exception
     */
    public function updateTask(Task $task, array $data): Task;

    /**
     * タスクを削除する
     *
     * @param Task $task
     * @return bool
     * @throws \Exception
     */
    public function deleteTask(Task $task): bool;

    /**
     * OpenAI APIにタスク分割を依頼し、その提案結果をDBに記録する。
     *
     * @param User $user 依頼ユーザー
     * @param string $originalTaskText 元のタスクのテキスト
     * @param string $context 分割の観点やプロンプト
     * @return array AIの提案結果と提案ID
     */
    public function proposeTaskDecomposition(User $user, string $originalTaskText, string $context): array;
    
    /**
     * AI提案のタスクリストをユーザーのタスクとして一括登録する。
     *
     * @param User $user ユーザー
     * @param int $proposalId 使用する提案ID
     * @param array $proposedTasks AIが提案したタスクデータ配列
     * @return Collection 登録されたタスクのコレクション
     */
    public function registerProposedTasks(User $user, int $proposalId, array $proposedTasks): \Illuminate\Support\Collection;

    /**
     * AI提案を採用し、提案されたタスクをユーザーのタスクとして保存する。
     *
     * @param User $user ユーザー
     * @param string|int $proposalId 採用する提案ID
     * @param array $tasks 採用するタスクデータ配列
     * @return array 登録されたタスクの配列
     */
    public function adoptProposal(User $user, string|int $proposalId, array $tasks): array;

    /**
     * ユーザーIDからユーザーを取得する。
     *
     * @param int $userId ユーザーID
     * @return User ユーザーモデル
     */
    public function getUserById(int $userId): User;
}