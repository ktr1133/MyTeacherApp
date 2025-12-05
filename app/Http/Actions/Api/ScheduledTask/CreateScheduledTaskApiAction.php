<?php

namespace App\Http\Actions\Api\ScheduledTask;

use App\Services\Profile\ProfileManagementServiceInterface;
use App\Http\Responders\Api\ScheduledTask\ScheduledTaskApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * スケジュールタスク作成画面情報取得API
 * 
 * GET /api/v1/scheduled-tasks/create
 * 
 * スケジュールタスク作成に必要な情報（グループメンバー等）を取得
 */
class CreateScheduledTaskApiAction
{
    /**
     * コンストラクタ
     * 
     * @param ProfileManagementServiceInterface $profileService プロフィール管理サービス
     * @param ScheduledTaskApiResponder $responder レスポンダー
     */
    public function __construct(
        protected ProfileManagementServiceInterface $profileService,
        protected ScheduledTaskApiResponder $responder
    ) {}

    /**
     * スケジュールタスク作成情報を取得
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
            return $this->responder->error('このグループでスケジュールタスクを作成する権限がありません。', 403);
        }

        // グループメンバーを取得
        $groupMembers = $this->profileService->getGroupMembers($groupId);

        return $this->responder->create([
            'group_id' => $groupId,
            'group_members' => $groupMembers,
        ]);
    }
}
