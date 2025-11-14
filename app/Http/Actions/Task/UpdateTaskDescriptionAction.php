<?php

namespace App\Http\Actions\Task;

use App\Http\Requests\Task\UpdateTaskDescriptionRequest;
use App\Http\Responders\Task\UpdateTaskDescriptionResponder;
use App\Models\Task;
use App\Services\Task\TaskManagementServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * タスクの説明文を更新するアクションクラス
 */
class UpdateTaskDescriptionAction
{
    protected TaskManagementServiceInterface $taskManagementService;
    protected UpdateTaskDescriptionResponder $responder;

    /**
     * コンストラクタ
     *
     * @param TaskManagementServiceInterface $taskManagementService
     * @param UpdateTaskDescriptionResponder $responder
     */
    public function __construct(
        TaskManagementServiceInterface $taskManagementService,
        UpdateTaskDescriptionResponder $responder
    ) {
        $this->taskManagementService = $taskManagementService;
        $this->responder = $responder;
    }

    /**
     * タスクの説明文を更新
     *
     * @param UpdateTaskDescriptionRequest $request
     * @param Task $task
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(UpdateTaskDescriptionRequest $request, Task $task)
    {
        try {
            $description = $request->validated()['description'];
            $userId = Auth::id();

            // サービスを通じて説明文を更新
            $updatedTask = $this->taskManagementService->updateTaskDescription(
                $task,
                $description,
                $userId
            );

            // 成功レスポンス
            return $this->responder->success($updatedTask);

        } catch (\Exception $e) {
            Log::error('Failed to update task description', [
                'task_id' => $task->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // エラーレスポンス
            return $this->responder->error($e->getMessage());
        }
    }
}