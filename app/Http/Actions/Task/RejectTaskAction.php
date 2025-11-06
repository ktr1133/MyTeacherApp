<?php

namespace App\Http\Actions\Task;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Task\TaskApprovalServiceInterface;

class RejectTaskAction
{
    /**
     * Constructor
     */
    public function __construct(
        private TaskApprovalServiceInterface $taskApprovalService
    ) {}

    /**
     * タスクを却下する。
     */
    public function __invoke(Request $request, Task $task)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->taskApprovalService->rejectTask($task, Auth::user(), $data['reason'] ?? null);

        return redirect()->route('tasks.pending-approvals')->with('success', 'タスクを却下しました。');
    }
}