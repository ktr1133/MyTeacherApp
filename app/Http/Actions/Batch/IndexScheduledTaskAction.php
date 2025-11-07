<?php

namespace App\Http\Actions\Batch;

use App\Repositories\Batch\ScheduledTaskRepositoryInterface;
use App\Responders\Batch\ScheduledTaskResponder;
use Illuminate\Http\Request;

class IndexScheduledTaskAction
{
    public function __construct(
        private ScheduledTaskRepositoryInterface $scheduledTaskRepository,
        private ScheduledTaskResponder $responder
    ) {}

    /**
     * スケジュールタスク一覧を表示
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
            abort(403, 'このグループのスケジュールタスクを表示する権限がありません。');
        }

        // スケジュールタスク一覧を取得
        $scheduledTasks = $this->scheduledTaskRepository->getByGroupId($groupId);

        return $this->responder->index([
            'scheduledTasks' => $scheduledTasks,
            'groupId' => $groupId,
        ]);
    }
}