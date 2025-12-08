<?php

namespace App\Http\Actions\Api\ScheduledTask;

use App\Models\ScheduledGroupTask;
use App\Http\Responders\Api\ScheduledTask\ScheduledTaskApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * スケジュールタスク実行履歴取得API
 * 
 * GET /api/v1/scheduled-tasks/{id}/history
 * 
 * スケジュールタスクの実行履歴を取得
 */
class GetScheduledTaskHistoryApiAction
{
    /**
     * コンストラクタ
     * 
     * @param ScheduledTaskApiResponder $responder レスポンダー
     */
    public function __construct(
        protected ScheduledTaskApiResponder $responder
    ) {}

    /**
     * 実行履歴を取得
     * 
     * @param Request $request
     * @param int $id スケジュールタスクID
     * @return JsonResponse
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        
        // スケジュールタスク取得
        $scheduledTask = ScheduledGroupTask::find($id);
        
        if (!$scheduledTask) {
            return $this->responder->error('スケジュールタスクが見つかりません。', 404);
        }

        // 権限チェック（グループメンバーのみ閲覧可能）
        if ($user->group_id !== $scheduledTask->group_id) {
            return $this->responder->error('このスケジュールタスクの履歴を表示する権限がありません。', 403);
        }

        // 実行履歴を取得（最新50件、降順）
        $executions = $scheduledTask->executions()
            ->orderBy('executed_at', 'desc')
            ->limit(50)
            ->get();

        return $this->responder->history($scheduledTask, $executions);
    }
}
