<?php

namespace App\Http\Actions\Api\ScheduledTask;

use App\Repositories\Batch\ScheduledTaskRepositoryInterface;
use App\Services\Profile\ProfileManagementServiceInterface;
use App\Http\Responders\Api\ScheduledTask\ScheduledTaskApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * スケジュールタスク編集画面情報取得API
 * 
 * GET /api/v1/scheduled-tasks/{id}/edit
 * 
 * スケジュールタスク編集に必要な情報（対象タスク + グループメンバー）を取得
 */
class EditScheduledTaskApiAction
{
    /**
     * コンストラクタ
     * 
     * @param ScheduledTaskRepositoryInterface $scheduledTaskRepository スケジュールタスクリポジトリ
     * @param ProfileManagementServiceInterface $profileService プロフィール管理サービス
     * @param ScheduledTaskApiResponder $responder レスポンダー
     */
    public function __construct(
        protected ScheduledTaskRepositoryInterface $scheduledTaskRepository,
        protected ProfileManagementServiceInterface $profileService,
        protected ScheduledTaskApiResponder $responder
    ) {}

    /**
     * スケジュールタスク編集情報を取得
     * 
     * @param Request $request
     * @param int $id スケジュールタスクID
     * @return JsonResponse
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        // スケジュールタスクを取得
        $scheduledTask = $this->scheduledTaskRepository->findById($id);

        if (!$scheduledTask) {
            return $this->responder->error('スケジュールタスクが見つかりません。', 404);
        }

        // 権限チェック
        if (!$request->user()->canEditGroup() || $request->user()->group_id !== $scheduledTask->group_id) {
            return $this->responder->error('このスケジュールタスクを編集する権限がありません。', 403);
        }

        // グループメンバーを取得
        $groupMembers = $this->profileService->getGroupMembers($scheduledTask->group_id);

        return $this->responder->edit([
            'scheduled_task' => $scheduledTask,
            'group_id' => $scheduledTask->group_id,
            'group_members' => $groupMembers,
        ]);
    }
}
