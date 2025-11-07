<?php

namespace App\Http\Actions\Batch;

use App\Repositories\Batch\ScheduledTaskRepositoryInterface;
use App\Responders\Batch\ScheduledTaskResponder;
use App\Services\Profile\ProfileManagementServiceInterface;
use Illuminate\Http\Request;

class EditScheduledTaskAction
{
    public function __construct(
        private ScheduledTaskRepositoryInterface $scheduledTaskRepository,
        private ScheduledTaskResponder $responder,
        private ProfileManagementServiceInterface $profileService
    ) {}

    /**
     * スケジュールタスク編集画面を表示
     */
    public function __invoke(Request $request, int $id)
    {
        // スケジュールタスクを取得
        $scheduledTask = $this->scheduledTaskRepository->findById($id);

        if (!$scheduledTask) {
            abort(404, 'スケジュールタスクが見つかりません。');
        }

        // 権限チェック
        if (!$request->user()->canEditGroup() || $request->user()->group_id !== $scheduledTask->group_id) {
            abort(403, 'このスケジュールタスクを編集する権限がありません。');
        }

        // グループメンバーを取得
        $groupMembers = $this->profileService->getGroupMembers($scheduledTask->group_id);

        return $this->responder->edit([
            'scheduledTask' => $scheduledTask,
            'groupId' => $scheduledTask->group_id,
            'groupMembers' => $groupMembers,
        ]);
    }
}