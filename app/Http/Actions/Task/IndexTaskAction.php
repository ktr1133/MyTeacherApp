<?php

namespace App\Http\Actions\Task;

use App\Services\Task\TaskListServiceInterface;
use App\Services\Task\TaskApprovalServiceInterface;
use App\Services\Notification\NotificationServiceInterface;
use Illuminate\Support\Facades\Auth;
use App\Services\Tag\TagServiceInterface;
use App\Responders\Task\TaskListResponder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Tag;

/**
 * メインメニュー画面 (タスク一覧) の表示を処理するアクションクラス。
 */
class IndexTaskAction
{
    protected TaskListServiceInterface $taskListService;
    protected TagServiceInterface $tagService;
    protected TaskApprovalServiceInterface $taskApprovalService;
    protected NotificationServiceInterface $notificationService;
    protected TaskListResponder $responder;

    public function __construct(
        TaskListServiceInterface $taskListService,
        TagServiceInterface $tagService,
        TaskApprovalServiceInterface $taskApprovalService,
        NotificationServiceInterface $notificationService,
        TaskListResponder $responder
    )
    {
        $this->taskListService = $taskListService;
        $this->tagService = $tagService;
        $this->taskApprovalService = $taskApprovalService;
        $this->notificationService = $notificationService;
        $this->responder = $responder;
    }

    /**
     * アクションの実行メソッド
     */
    public function __invoke(Request $request): Response|\Illuminate\View\View
    {
        $user = $request->user();
        $userId = $user->id;
        $filters = $request->only(['search', 'status', 'priority', 'tags']);
        
        // 無限スクロール用の初回表示（50件のみ）
        $perPage = 50;
        $paginatedResult = $this->taskListService->getTasksForUserPaginated($userId, $filters, 1, $perPage);
        
        $tags = $this->tagService->getByUserId($userId);

        // 未読通知件数を取得
        $notificationData = $this->notificationService->getUnreadCountWithNew($userId);

        $data = [
            'tasks' => $paginatedResult['tasks'],
            'tags' => $tags,
            'notificationCount' => $notificationData['unread_count'] ?? 0,
            'hasMore' => $paginatedResult['has_more'],
            'nextPage' => $paginatedResult['next_page'],
            'perPage' => $perPage,
        ];

        // 大人向けダッシュボード（既存）
        return $this->responder->respond($data);
    }
}