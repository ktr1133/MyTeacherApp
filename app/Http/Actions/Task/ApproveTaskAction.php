<?php

namespace App\Http\Actions\Task;

use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use App\Services\Task\TaskApprovalServiceInterface;

/**
 * タスク承認アクション。
 */
class ApproveTaskAction
{
    /**
     * Constructor
     */
    public function __construct(
        private TaskApprovalServiceInterface $taskApprovalService
    ) {}

    public function __invoke(Task $task)
    {
        $this->taskApprovalService->approveTask($task, Auth::user());

        return redirect()
            ->route('tasks.pending-approvals')
            ->with('success', 'タスクを承認しました。')
            ->with('avatar_event', config('const.avatar_events.task_completed'));
    }
}