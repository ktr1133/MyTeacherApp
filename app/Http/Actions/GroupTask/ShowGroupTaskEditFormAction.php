<?php

namespace App\Http\Actions\GroupTask;

use App\Services\Task\TaskManagementServiceInterface;
use App\Http\Responders\GroupTask\GroupTaskResponder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

/**
 * グループタスク編集フォーム表示Action
 * 
 * 特定のグループタスクの編集フォームを表示
 */
class ShowGroupTaskEditFormAction
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
     * グループタスク編集フォームを表示
     *
     * @param Request $request
     * @param string $groupTaskId
     * @return View
     */
    public function __invoke(Request $request, string $groupTaskId): View
    {
        $user = $request->user();
        
        // 権限チェック
        if (!$user->canEditGroup()) {
            abort(403, 'この操作を実行する権限がありません。');
        }

        try {
            // グループタスク取得
            $groupTask = $this->taskManagementService->findEditableGroupTask($user, $groupTaskId);

            if (!$groupTask) {
                abort(404, '指定されたグループタスクが見つかりません。');
            }

            Log::info('グループタスク編集フォーム表示', [
                'user_id' => $user->id,
                'group_task_id' => $groupTaskId,
            ]);

            return $this->responder->edit($groupTask);
            
        } catch (\Exception $e) {
            Log::error('グループタスク編集フォーム表示エラー', [
                'user_id' => $user->id,
                'group_task_id' => $groupTaskId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            abort(500, 'グループタスクの取得に失敗しました。');
        }
    }
}
