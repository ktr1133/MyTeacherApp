<?php

namespace App\Http\Actions\Task;

use App\Services\Task\TaskManagementServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * タスク更新処理を実行するアクション。
 * バリデーションと認可を担当し、ビジネスロジックはServiceに委譲する。
 */
class UpdateTaskAction
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
     * タスク更新処理
     *
     * @param Request $request
     * @param int $id タスクID
     * @return RedirectResponse
     */
    public function __invoke(Request $request, int $id): RedirectResponse
    {
        // タスク取得
        $task = $this->taskManagementService->findById($id);

        if (!$task) {
            abort(404, 'タスクが見つかりません');
        }
        // 認可チェック（自分のタスクのみ編集可能）
        if ($task->user_id !== $request->user()->id) {
            abort(403, 'このタスクを編集する権限がありません');
        }

        // バリデーション
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'span' => 'required|integer|in:1,2,3',
            'due_date' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'integer|exists:tags,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Serviceに更新処理を委譲
            $this->taskManagementService->updateTask(
                $task,
                $validator->validated()
            );

            return redirect()->route('dashboard')
                ->with('success', 'タスクを更新しました');

        } catch (\Exception $e) {
            Log::error('Failed to update task', [
                'task_id' => $task->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'タスクの更新に失敗しました')
                ->withInput();
        }
    }
}