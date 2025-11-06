<?php

namespace App\Http\Actions\Task;

use Illuminate\Support\Facades\Auth;
use App\Services\Task\TaskApprovalServiceInterface;
use Illuminate\View\View;

/**
 * 保留中の承認リクエストの一覧を表示するアクション。
 */
class ListPendingApprovalsAction
{
    /**
     * Constructor
     */
    public function __construct(
        private TaskApprovalServiceInterface $taskApprovalService
    ) {}

    /**
     * 保留中の承認リクエストの一覧を表示する。
     */
    public function __invoke(): View
    {
        $pendingTasks = $this->taskApprovalService->getPendingApprovals(Auth::user());


        return view('tasks.pending-approvals', [
            'pendingTasks' => $pendingTasks,
        ]);
    }
}