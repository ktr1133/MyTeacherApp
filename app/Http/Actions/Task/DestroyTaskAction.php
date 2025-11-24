<?php

namespace App\Http\Actions\Task;

use App\Models\Task;
use App\Services\Task\TaskManagementServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * タスク削除処理を実行するアクション。
 * 
 * 責務:
 * - リクエストの受付
 * - 認可チェック（自分のタスクのみ削除可能）
 * - Serviceへの削除処理委譲
 * - リダイレクトレスポンス生成
 */
class DestroyTaskAction
{
    protected TaskManagementServiceInterface $taskManagementService;

    /**
     * コンストラクタ。タスク管理サービスを注入する。
     *
     * @param TaskManagementServiceInterface $taskManagementService
     */
    public function __construct(TaskManagementServiceInterface $taskManagementService)
    {
        $this->taskManagementService = $taskManagementService;
    }

    /**
     * タスク削除処理
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $taskId = $request->input('task_id');
        $task = $this->taskManagementService->findById((int)$taskId);

        // 認可チェック（自分のタスクのみ削除可能）
        if ($task->user_id !== $request->user()->id) {
            abort(403, 'このタスクを削除する権限がありません');
        }

        try {
            // Serviceに削除処理を委譲
            $this->taskManagementService->deleteTask($task);

            return redirect()->route('dashboard')
                ->with('success', 'タスクを削除しました')
                ->with('avatar_event', 'task_deleted');

        } catch (\Exception $e) {
            Log::error('Failed to delete task', [
                'task_id' => $task->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'タスクの削除に失敗しました');
        }
    }
}