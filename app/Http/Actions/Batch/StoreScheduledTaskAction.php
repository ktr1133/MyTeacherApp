<?php

namespace App\Http\Actions\Batch;

use App\Services\Batch\ScheduledTaskServiceInterface;
use App\Http\Requests\Batch\StoreScheduledTaskRequest;
use App\Responders\Batch\ScheduledTaskResponder;
use Illuminate\Support\Facades\Log;

class StoreScheduledTaskAction
{
    public function __construct(
        private ScheduledTaskServiceInterface $scheduledTaskService,
        private ScheduledTaskResponder $responder
    ) {}

    /**
     * スケジュールタスクを作成
     */
    public function __invoke(StoreScheduledTaskRequest $request)
    {
        try {
            $data = $request->validated();
            $data['created_by'] = $request->user()->id;

            $scheduledTask = $this->scheduledTaskService->createScheduledTask($data);

            return redirect()
                ->route('batch.scheduled-tasks.index', ['group_id' => $data['group_id']])
                ->with('status', 'scheduled-task-created')
                ->with('avatar_event', config('const.avatar_events.group_task_created'));

        } catch (\Exception $e) {
            Log::error('Failed to create scheduled task', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'スケジュールタスクの作成に失敗しました。']);
        }
    }
}