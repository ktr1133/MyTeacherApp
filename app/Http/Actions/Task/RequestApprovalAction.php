<?php

namespace App\Http\Actions\Task;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Task\TaskApprovalServiceInterface;

class RequestApprovalAction
{
    /**
     * Constructor
     */
    public function __construct(
        private TaskApprovalServiceInterface $taskApprovalService
    ) {}

    public function __invoke(Request $request, Task $task)
    {
        $this->taskApprovalService->requestApproval($task, Auth::user());

        return redirect()->route('dashboard')->with('success', '完了申請しました。承認をお待ちください。');
    }
}