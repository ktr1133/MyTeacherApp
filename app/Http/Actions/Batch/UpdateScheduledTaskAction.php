<?php

namespace App\Http\Actions\Batch;

use App\Services\Batch\ScheduledTaskServiceInterface;
use App\Repositories\Batch\ScheduledTaskRepositoryInterface;
use App\Http\Requests\Batch\UpdateScheduledTaskRequest;
use Illuminate\Support\Facades\Log;

class UpdateScheduledTaskAction
{
    public function __construct(
        private ScheduledTaskServiceInterface $scheduledTaskService,
        private ScheduledTaskRepositoryInterface $scheduledTaskRepository
    ) {}

    /**
     * スケジュールタスクを更新
     */
    public function __invoke(UpdateScheduledTaskRequest $request, int $id)
    {
        try {
            // スケジュールタスクを取得
            $scheduledTask = $this->scheduledTaskRepository->findById($id);

            if (!$scheduledTask) {
                abort(404, 'スケジュールタスクが見つかりません。');
            }

            // 権限チェック
            if (!$request->user()->canEditGroup() || $request->user()->group_id !== $scheduledTask->group_id) {
                abort(403, 'このスケジュールタスクを更新する権限がありません。');
            }

            $data = $request->validated();
            
            $result = $this->scheduledTaskService->updateScheduledTask($id, $data);

            if (!$result) {
                throw new \Exception('Failed to update scheduled task');
            }

            return redirect()
                ->route('batch.scheduled-tasks.index', ['group_id' => $scheduledTask->group_id])
                ->with('status', 'scheduled-task-updated');

        } catch (\Exception $e) {
            Log::error('Failed to update scheduled task', [
                'scheduled_task_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'スケジュールタスクの更新に失敗しました。']);
        }
    }
}