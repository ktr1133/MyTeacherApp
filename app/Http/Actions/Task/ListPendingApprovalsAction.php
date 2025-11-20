<?php

namespace App\Http\Actions\Task;

use App\Services\Approval\ApprovalMergeServiceInterface;
use App\Services\Task\TaskApprovalServiceInterface;
use App\Services\Token\TokenPurchaseApprovalServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * 承認待ちタスク・トークン購入の一覧を表示するアクション
 */
class ListPendingApprovalsAction
{
    protected TaskApprovalServiceInterface $taskApprovalService;
    protected TokenPurchaseApprovalServiceInterface $tokenPurchaseApprovalService;
    protected ApprovalMergeServiceInterface $approvalMergeService;

    /**
     * Constructor
     */
    public function __construct(
        TaskApprovalServiceInterface $taskApprovalService,
        TokenPurchaseApprovalServiceInterface $tokenPurchaseApprovalService,
        ApprovalMergeServiceInterface $approvalMergeService
    ) {
        $this->taskApprovalService = $taskApprovalService;
        $this->tokenPurchaseApprovalService = $tokenPurchaseApprovalService;
        $this->approvalMergeService = $approvalMergeService;
    }

    /**
     * 承認待ち一覧を表示
     *
     * @param Request $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        $user = Auth::user();

        // タスクの承認待ちデータを取得
        $pendingTasks = $this->taskApprovalService->getPendingTasksForApprover($user);

        // トークン購入の承認待ちデータを取得
        $pendingTokenPurchases = $this->tokenPurchaseApprovalService->getPendingRequestsForParent($user);

        // データを統合してページネーション
        $approvals = $this->approvalMergeService->mergeAndSortApprovals(
            $pendingTasks,
            $pendingTokenPurchases,
            15 // 1ページあたり15件
        );

        return view('tasks.pending-approvals', [
            'approvals' => $approvals,
        ]);
    }
}