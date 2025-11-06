<?php

namespace App\Repositories\Task;

use App\Models\TaskProposal;

interface TaskProposalRepositoryInterface
{
    /**
     * タスク提案を作成する。
     *
     * @param array $data 提案データ配列
     * @return TaskProposal 作成されたタスク提案モデル
     */
    public function create(array $data): TaskProposal;

    /**
     * タスク提案をIDで検索する。
     *
     * @param int $id タスク提案ID
     * @return TaskProposal|null 見つかったタスク提案モデル、またはnull
     */
    public function find(int $id): ?TaskProposal;

    /**
     * タスク提案を採用済みとしてマークする。
     *
     * @param int $id タスク提案ID
     * @param array<int> $taskIds 採用するタスクのID配列
     * @return void
     */
    public function markAsAdopted(int $id, array $taskIds = []): void;
}