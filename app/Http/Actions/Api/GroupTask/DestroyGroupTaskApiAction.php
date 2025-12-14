<?php

namespace App\Http\Actions\Api\GroupTask;

use App\Services\Task\TaskManagementServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * グループタスク削除API Action
 * 
 * モバイルアプリ用: グループタスクを論理削除（同一group_task_idの全タスク）
 */
class DestroyGroupTaskApiAction
{
    /**
     * コンストラクタ
     *
     * @param TaskManagementServiceInterface $taskManagementService
     */
    public function __construct(
        protected TaskManagementServiceInterface $taskManagementService
    ) {}

    /**
     * グループタスクを削除
     *
     * @param Request $request
     * @param string $groupTaskId
     * @return JsonResponse
     */
    public function __invoke(Request $request, string $groupTaskId): JsonResponse
    {
        $user = $request->user();
        
        // 権限チェック
        if (!$user->canEditGroup()) {
            return response()->json([
                'message' => 'この操作を実行する権限がありません。',
            ], 403);
        }

        try {
            // グループタスク存在確認
            $groupTask = $this->taskManagementService->findEditableGroupTask($user, $groupTaskId);
            
            if (!$groupTask) {
                return response()->json([
                    'message' => '指定されたグループタスクが見つかりません。',
                ], 404);
            }

            // グループタスク削除（論理削除）
            $this->taskManagementService->deleteGroupTask($user, $groupTaskId);
            
            Log::info('[API] グループタスク削除成功', [
                'user_id' => $user->id,
                'group_task_id' => $groupTaskId,
            ]);

            return response()->json([
                'message' => 'グループタスクを削除しました。',
            ]);
            
        } catch (\Exception $e) {
            Log::error('[API] グループタスク削除エラー', [
                'user_id' => $user->id,
                'group_task_id' => $groupTaskId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'グループタスクの削除に失敗しました。',
            ], 500);
        }
    }
}
