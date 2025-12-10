<?php

namespace App\Http\Actions\GroupTask;

use App\Services\Task\TaskManagementServiceInterface;
use App\Http\Responders\GroupTask\GroupTaskResponder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

/**
 * グループタスク一覧表示Action
 * 
 * ログインユーザーが作成した編集可能なグループタスクの一覧を表示
 */
class ListGroupTasksAction
{
    /**
     * コンストラクタ
     *
     * @param TaskManagementServiceInterface $taskManagementService
     * @param GroupTaskResponder $responder
     */
    public function __construct(
        protected TaskManagementServiceInterface $taskManagementService,
        protected GroupTaskResponder $responder
    ) {}

    /**
     * グループタスク一覧を表示
     *
     * @param Request $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        
        // 権限チェック
        if (!$user->canEditGroup()) {
            abort(403, 'この操作を実行する権限がありません。');
        }

        try {
            // グループタスク取得（group_task_id単位でグループ化）
            $groupTasks = $this->taskManagementService->getEditableGroupTasks($user);
            
            Log::info('グループタスク一覧取得', [
                'user_id' => $user->id,
                'count' => $groupTasks->count(),
            ]);

            return $this->responder->index($groupTasks);
            
        } catch (\Exception $e) {
            Log::error('グループタスク一覧取得エラー', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            abort(500, 'グループタスクの取得に失敗しました。');
        }
    }
}
