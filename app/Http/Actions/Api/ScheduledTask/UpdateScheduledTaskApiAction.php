<?php

namespace App\Http\Actions\Api\ScheduledTask;

use App\Services\Batch\ScheduledTaskServiceInterface;
use App\Repositories\Batch\ScheduledTaskRepositoryInterface;
use App\Http\Requests\Api\ScheduledTask\UpdateScheduledTaskRequest;
use App\Http\Responders\Api\ScheduledTask\ScheduledTaskApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * スケジュールタスク更新API
 * 
 * PUT /api/v1/scheduled-tasks/{id}
 * 
 * スケジュールタスクの情報を更新
 */
class UpdateScheduledTaskApiAction
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
     * スケジュールタスクを更新
     * 
     * @param UpdateScheduledTaskRequest $request
     * @param int $id スケジュールタスクID
     * @return JsonResponse
     */
    public function __invoke(UpdateScheduledTaskRequest $request, int $id): JsonResponse
    {
        try {
            // スケジュールタスクを取得
            $scheduledTask = $this->scheduledTaskRepository->findById($id);

            if (!$scheduledTask) {
                return $this->responder->error('スケジュールタスクが見つかりません。', 404);
            }

            // 権限チェック
            if (!$request->user()->canEditGroup() || $request->user()->group_id !== $scheduledTask->group_id) {
                return $this->responder->error('このスケジュールタスクを更新する権限がありません。', 403);
            }

            $data = $request->validated();
            
            $result = $this->scheduledTaskService->updateScheduledTask($id, $data);

            if (!$result) {
                throw new \Exception('Failed to update scheduled task');
            }

            // 更新後のデータを取得
            $updatedTask = $this->scheduledTaskRepository->findById($id);

            return $this->responder->update($updatedTask);

        } catch (\Exception $e) {
            Log::error('Failed to update scheduled task', [
                'scheduled_task_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return $this->responder->error('スケジュールタスクの更新に失敗しました。', 500);
        }
    }
}
