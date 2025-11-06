<?php

namespace App\Services\Task;

use App\Models\User;
use App\Models\TaskProposal;

interface TaskProposalServiceInterface
{
    /**
     * タスク提案を作成する。
     *
     * @param User $user 提案を行うユーザー
     * @param string $originalText 元のタスクテキスト
     * @param string $span タスク分解の範囲
     * @param string|null $context 追加コンテキスト（任意）
     * @param bool $isRefinement 洗練要求かどうか
     * @return TaskProposal 作成されたタスク提案モデル
     */
    public function createProposal(
        User $user,
        string $originalText,
        string $span,
        ?string $context,
        bool $isRefinement
    ): TaskProposal;

    /**
     * 指定された提案を採用済みにマークし、関連タスクを更新する。
     *
     * @param int $proposalId 採用する提案のID
     * @param array<int> $taskIds 採用するタスクのID配列
     * @return void
     */
    public function markAsAdopted(int $proposalId, array $taskIds): void;
}