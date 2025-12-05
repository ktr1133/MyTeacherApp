<?php

namespace App\Http\Actions\Api\ScheduledTask;

use App\Services\Batch\ScheduledTaskServiceInterface;
use App\Http\Requests\Api\ScheduledTask\StoreScheduledTaskRequest;
use App\Http\Responders\Api\ScheduledTask\ScheduledTaskApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * スケジュールタスク作成API
 * 
 * POST /api/v1/scheduled-tasks
 * 
 * 新規スケジュールタスクを作成
 */
class StoreScheduledTaskApiAction
{
    /**
     * コンストラクタ
     * 
     * @param ScheduledTaskServiceInterface $scheduledTaskService スケジュールタスクサービス
     * @param ScheduledTaskApiResponder $responder レスポンダー
     */
    public function __construct(
        protected ScheduledTaskServiceInterface $scheduledTaskService,
        protected ScheduledTaskApiResponder $responder
    ) {}

    /**
     * スケジュールタスクを作成
     * 
     * @param StoreScheduledTaskRequest $request
     * @return JsonResponse
     */
    public function __invoke(StoreScheduledTaskRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['created_by'] = $request->user()->id;

            $scheduledTask = $this->scheduledTaskService->createScheduledTask($data);

            return $this->responder->store($scheduledTask);

        } catch (\Exception $e) {
            Log::error('Failed to create scheduled task', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return $this->responder->error('スケジュールタスクの作成に失敗しました。', 500);
        }
    }
}
