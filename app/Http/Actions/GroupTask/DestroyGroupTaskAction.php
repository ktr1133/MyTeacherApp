<?php

namespace App\Http\Actions\GroupTask;

use App\Services\Task\TaskManagementServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * グループタスク削除Action
 * 
 * グループタスクを論理削除（同一group_task_idの全タスク）
 */
class DestroyGroupTaskAction
{
    /**
     * コンストラクタ
     *
     * @param TaskManagementServiceInterface $taskManagementService
     */
    public function __construct(
        protected TaskManagementServiceInterface $taskManagementService
    ) {}

    /**
     * グループタスクを削除
     *
     * @param Request $request
     * @param string $groupTaskId
     * @return RedirectResponse
     */
    public function __invoke(Request $request, string $groupTaskId): RedirectResponse
    {
        $user = $request->user();
        
        // 権限チェック
        if (!$user->canEditGroup()) {
            return redirect()
                ->route('group-tasks.index')
                ->withErrors(['error' => 'この操作を実行する権限がありません。']);
        }

        try {
            // グループタスク存在確認
            $groupTask = $this->taskManagementService->findEditableGroupTask($user, $groupTaskId);
            
            if (!$groupTask) {
                return redirect()
                    ->route('group-tasks.index')
                    ->withErrors(['error' => '指定されたグループタスクが見つかりません。']);
            }

            // グループタスク削除（論理削除）
            $this->taskManagementService->deleteGroupTask($user, $groupTaskId);
            
            Log::info('グループタスク削除成功', [
                'user_id' => $user->id,
                'group_task_id' => $groupTaskId,
            ]);

            return redirect()
                ->route('group-tasks.index')
                ->with('success', 'グループタスクを削除しました。');
            
        } catch (\Exception $e) {
            Log::error('グループタスク削除エラー', [
                'user_id' => $user->id,
                'group_task_id' => $groupTaskId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->withErrors(['error' => 'グループタスクの削除に失敗しました。']);
        }
    }
}
