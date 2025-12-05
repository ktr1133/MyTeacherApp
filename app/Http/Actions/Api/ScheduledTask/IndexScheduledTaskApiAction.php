<?php

namespace App\Http\Actions\Api\ScheduledTask;

use App\Repositories\Batch\ScheduledTaskRepositoryInterface;
use App\Http\Responders\Api\ScheduledTask\ScheduledTaskApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * スケジュールタスク一覧取得API
 * 
 * GET /api/v1/scheduled-tasks
 * 
 * グループのスケジュールタスク一覧を取得
 */
class IndexScheduledTaskApiAction
{
    /**
     * コンストラクタ
     * 
     * @param ScheduledTaskRepositoryInterface $scheduledTaskRepository スケジュールタスクリポジトリ
     * @param ScheduledTaskApiResponder $responder レスポンダー
     */
    public function __construct(
        protected ScheduledTaskRepositoryInterface $scheduledTaskRepository,
        protected ScheduledTaskApiResponder $responder
    ) {}

    /**
     * スケジュールタスク一覧を取得
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $groupId = $request->query('group_id');
        
        if (!$groupId) {
            return $this->responder->error('group_idパラメータが必要です。', 400);
        }

        // 権限チェック
        $user = $request->user();
        if (!$user->canEditGroup() || $user->group_id !== (int)$groupId) {
            return $this->responder->error('このグループのスケジュールタスクを表示する権限がありません。', 403);
        }

        // スケジュールタスク一覧を取得
        $scheduledTasks = $this->scheduledTaskRepository->getByGroupId($groupId);

        return $this->responder->index($scheduledTasks);
    }
}
