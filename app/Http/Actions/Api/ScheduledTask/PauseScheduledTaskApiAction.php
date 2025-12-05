<?php

namespace App\Http\Actions\Api\ScheduledTask;

use App\Services\Batch\ScheduledTaskServiceInterface;
use App\Repositories\Batch\ScheduledTaskRepositoryInterface;
use App\Http\Responders\Api\ScheduledTask\ScheduledTaskApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * スケジュールタスク一時停止API
 * 
 * POST /api/v1/scheduled-tasks/{id}/pause
 * 
 * スケジュールタスクの実行を一時停止
 */
class PauseScheduledTaskApiAction
{
    /**
     * コンストラクタ
     * 
     * @param ScheduledTaskServiceInterface $scheduledTaskService スケジュールタスクサービス
     * @param ScheduledTaskRepositoryInterface $scheduledTaskRepository スケジュールタスクリポジトリ
     * @param ScheduledTaskApiResponder $responder レスポンダー
     */
    public function __construct(
        protected ScheduledTaskServiceInterface $scheduledTaskService,
        protected ScheduledTaskRepositoryInterface $scheduledTaskRepository,
        protected ScheduledTaskApiResponder $responder
    ) {}

    /**
     * スケジュールタスクを一時停止
     * 
     * @param Request $request
     * @param int $id スケジュールタスクID
     * @return JsonResponse
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        try {
            // スケジュールタスクを取得
            $scheduledTask = $this->scheduledTaskRepository->findById($id);

            if (!$scheduledTask) {
                return $this->responder->error('スケジュールタスクが見つかりません。', 404);
            }

            // 権限チェック
            if (!$request->user()->canEditGroup() || $request->user()->group_id !== $scheduledTask->group_id) {
                return $this->responder->error('このスケジュールタスクを一時停止する権限がありません。', 403);
            }

            $result = $this->scheduledTaskService->pauseScheduledTask($id);

            if (!$result) {
                throw new \Exception('Failed to pause scheduled task');
            }

            // 更新後のデータを取得
            $updatedTask = $this->scheduledTaskRepository->findById($id);

            return $this->responder->pause($updatedTask);

        } catch (\Exception $e) {
            Log::error('Failed to pause scheduled task', [
                'scheduled_task_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return $this->responder->error('スケジュールタスクの一時停止に失敗しました。', 500);
        }
    }
}
