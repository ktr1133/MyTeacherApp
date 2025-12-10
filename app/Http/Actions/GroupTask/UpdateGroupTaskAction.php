<?php

namespace App\Http\Actions\GroupTask;

use App\Services\Task\TaskManagementServiceInterface;
use App\Http\Requests\GroupTask\UpdateGroupTaskRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * グループタスク更新Action
 * 
 * グループタスクの情報を一括更新（同一group_task_idの全タスク）
 */
class UpdateGroupTaskAction
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
     * グループタスクを更新
     *
     * @param UpdateGroupTaskRequest $request
     * @param string $groupTaskId
     * @return RedirectResponse
     */
    public function __invoke(UpdateGroupTaskRequest $request, string $groupTaskId): RedirectResponse
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

            // グループタスク更新
            $this->taskManagementService->updateGroupTask($user, $groupTaskId, $request->validated());
            
            Log::info('グループタスク更新成功', [
                'user_id' => $user->id,
                'group_task_id' => $groupTaskId,
            ]);

            return redirect()
                ->route('group-tasks.index')
                ->with('success', 'グループタスクを更新しました。');
            
        } catch (\Exception $e) {
            Log::error('グループタスク更新エラー', [
                'user_id' => $user->id,
                'group_task_id' => $groupTaskId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->withErrors(['error' => 'グループタスクの更新に失敗しました。'])
                ->withInput();
        }
    }
}
