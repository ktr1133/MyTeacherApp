<?php

namespace App\Http\Actions\Batch;

use App\Responders\Batch\ScheduledTaskResponder;
use App\Services\Batch\ScheduledTaskServiceInterface;
use App\Services\Profile\ProfileManagementServiceInterface;
use Illuminate\Http\Request;

class CreateScheduledTaskAction
{
    public function __construct(
        private ScheduledTaskResponder $responder,
        private ScheduledTaskServiceInterface $scheduledTaskService,
        private ProfileManagementServiceInterface $profileService
    ) {}

    /**
     * スケジュールタスク作成画面を表示
     */
    public function __invoke(Request $request)
    {
        $groupId = $request->query('group_id');
        
        if (!$groupId) {
            return redirect()
                ->route('profile.edit')
                ->withErrors(['error' => 'グループを選択してください。']);
        }

        // 権限チェック
        $user = $request->user();
        if (!$user->canEditGroup() || $user->group_id !== (int)$groupId) {
            abort(403, 'このグループでスケジュールタスクを作成する権限がありません。');
        }

        // グループメンバーを取得
        $groupMembers = $this->profileService->getGroupMembers($groupId);

        return $this->responder->create([
            'groupId' => $groupId,
            'groupMembers' => $groupMembers,
        ]);
    }
}