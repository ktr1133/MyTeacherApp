<?php

namespace App\Http\Actions\Batch;

use App\Services\Batch\ScheduledTaskServiceInterface;
use App\Repositories\Batch\ScheduledTaskRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PauseScheduledTaskAction
{
    public function __construct(
        private ScheduledTaskServiceInterface $scheduledTaskService,
        private ScheduledTaskRepositoryInterface $scheduledTaskRepository
    ) {}

    /**
     * スケジュールタスクを一時停止
     */
    public function __invoke(Request $request, int $id)
    {
        try {
            // スケジュールタスクを取得
            $scheduledTask = $this->scheduledTaskRepository->findById($id);

            if (!$scheduledTask) {
                return response()->json([
                    'success' => false,
                    'message' => 'スケジュールタスクが見つかりません。',
                ], 404);
            }

            // 権限チェック
            if (!$request->user()->canEditGroup() || $request->user()->group_id !== $scheduledTask->group_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'このスケジュールタスクを一時停止する権限がありません。',
                ], 403);
            }

            $result = $this->scheduledTaskService->pauseScheduledTask($id);

            if (!$result) {
                throw new \Exception('Failed to pause scheduled task');
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'スケジュールタスクを一時停止しました。',
                ]);
            }

            return redirect()
                ->back()
                ->with('status', 'scheduled-task-paused');

        } catch (\Exception $e) {
            Log::error('Failed to pause scheduled task', [
                'scheduled_task_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'スケジュールタスクの一時停止に失敗しました。',
                ], 500);
            }

            return redirect()
                ->back()
                ->withErrors(['error' => 'スケジュールタスクの一時停止に失敗しました。']);
        }
    }
}