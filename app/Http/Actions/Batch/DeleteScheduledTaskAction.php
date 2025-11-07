<?php

namespace App\Http\Actions\Batch;

use App\Services\Batch\ScheduledTaskServiceInterface;
use App\Repositories\Batch\ScheduledTaskRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeleteScheduledTaskAction
{
    public function __construct(
        private ScheduledTaskServiceInterface $scheduledTaskService,
        private ScheduledTaskRepositoryInterface $scheduledTaskRepository
    ) {}

    /**
     * スケジュールタスクを削除
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
                    'message' => 'このスケジュールタスクを削除する権限がありません。',
                ], 403);
            }

            $groupId = $scheduledTask->group_id;
            $result = $this->scheduledTaskService->deleteScheduledTask($id);

            if (!$result) {
                throw new \Exception('Failed to delete scheduled task');
            }

            // Ajax リクエストの場合はJSON、それ以外はリダイレクト
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'スケジュールタスクを削除しました。',
                ]);
            }

            return redirect()
                ->route('batch.scheduled-tasks.index', ['group_id' => $groupId])
                ->with('status', 'scheduled-task-deleted');

        } catch (\Exception $e) {
            Log::error('Failed to delete scheduled task', [
                'scheduled_task_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'スケジュールタスクの削除に失敗しました。',
                ], 500);
            }

            return redirect()
                ->back()
                ->withErrors(['error' => 'スケジュールタスクの削除に失敗しました。']);
        }
    }
}