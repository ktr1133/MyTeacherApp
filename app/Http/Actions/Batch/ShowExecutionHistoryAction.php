<?php
// filepath: app/Http/Actions/Batch/ShowExecutionHistoryAction.php

namespace App\Http\Actions\Batch;

use App\Repositories\Batch\ScheduledTaskRepositoryInterface;
use App\Responders\Batch\ScheduledTaskResponder;
use Illuminate\Http\Request;

class ShowExecutionHistoryAction
{
    public function __construct(
        private ScheduledTaskRepositoryInterface $scheduledTaskRepository,
        private ScheduledTaskResponder $responder
    ) {}

    /**
     * スケジュールタスクの実行履歴を表示
     */
    public function __invoke(Request $request, int $id)
    {
        // スケジュールタスクを取得
        $scheduledTask = $this->scheduledTaskRepository->findById($id);
        
        if (!$scheduledTask) {
            abort(404, 'スケジュールタスクが見つかりません。');
        }

        // 権限チェック
        if ($request->user()->group_id !== $scheduledTask->group_id) {
            abort(403, 'この実行履歴を表示する権限がありません。');
        }

        // 実行履歴を取得
        $executions = $this->scheduledTaskRepository->getExecutionHistory($id, 100);

        return $this->responder->history([
            'scheduledTask' => $scheduledTask,
            'executions' => $executions,
            'groupId' => $scheduledTask->group_id,
        ]);
    }
}