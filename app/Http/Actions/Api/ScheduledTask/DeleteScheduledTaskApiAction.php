<?php

namespace App\Http\Actions\Api\ScheduledTask;

use App\Services\Batch\ScheduledTaskServiceInterface;
use App\Repositories\Batch\ScheduledTaskRepositoryInterface;
use App\Http\Responders\Api\ScheduledTask\ScheduledTaskApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * スケジュールタスク削除API
 * 
 * DELETE /api/v1/scheduled-tasks/{id}
 * 
 * スケジュールタスクを削除
 */
class DeleteScheduledTaskApiAction
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
     * スケジュールタスクを削除
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
                return $this->responder->error('このスケジュールタスクを削除する権限がありません。', 403);
            }

            $result = $this->scheduledTaskService->deleteScheduledTask($id);

            if (!$result) {
                throw new \Exception('Failed to delete scheduled task');
            }

            return $this->responder->delete();

        } catch (\Exception $e) {
            Log::error('Failed to delete scheduled task', [
                'scheduled_task_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return $this->responder->error('スケジュールタスクの削除に失敗しました。', 500);
        }
    }
}
