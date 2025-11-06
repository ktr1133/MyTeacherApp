<?php

namespace App\Repositories\Task;

use App\Models\TaskProposal;

class EloquentTaskProposalRepository implements TaskProposalRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function create(array $data): TaskProposal
    {
        return TaskProposal::create($data);
    }

    /**
     * @inheritDoc
     */
    public function find(int $id): ?TaskProposal
    {
        return TaskProposal::find($id);
    }

    /**
     * @inheritDoc
     */
    public function markAsAdopted(int $id, array $taskIds = []): void
    {
        TaskProposal::whereKey($id)->update(['was_adopted' => true]);
    }
}