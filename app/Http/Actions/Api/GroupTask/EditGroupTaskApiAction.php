<?php

namespace App\Http\Actions\Api\GroupTask;

use App\Services\Task\TaskManagementServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * グループタスク編集データ取得API Action
 * 
 * モバイルアプリ用: 特定のグループタスクの編集用データを返却
 */
class EditGroupTaskApiAction
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
     * グループタスク編集データを取得
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
            // グループタスク取得
            $groupTask = $this->taskManagementService->findEditableGroupTask($user, $groupTaskId);
            
            if (!$groupTask) {
                return response()->json([
                    'message' => '指定されたグループタスクが見つかりません。',
                ], 404);
            }

            Log::info('[API] グループタスク編集データ取得', [
                'user_id' => $user->id,
                'group_task_id' => $groupTaskId,
            ]);

            // レスポンス整形（$groupTaskは配列）
            // due_dateは文字列（長期）またはCarbonインスタンス（短期・中期）の可能性がある
            $dueDate = $groupTask['due_date'];
            if ($dueDate instanceof \Carbon\Carbon) {
                $dueDate = $dueDate->format('Y-m-d');
            }
            
            $data = [
                'group_task_id' => $groupTask['group_task_id'],
                'title' => $groupTask['title'],
                'description' => $groupTask['description'],
                'span' => $groupTask['span'],
                'reward' => $groupTask['reward'],
                'due_date' => $dueDate,
                'requires_approval' => (bool) $groupTask['requires_approval'],
                'requires_image' => (bool) $groupTask['requires_image'],
                'assigned_count' => $groupTask['assigned_count'] ?? 0,
                'created_at' => $groupTask['created_at']?->toIso8601String(),
                'updated_at' => $groupTask['updated_at']?->toIso8601String(),
            ];

            return response()->json($data);
            
        } catch (\Exception $e) {
            Log::error('[API] グループタスク編集データ取得エラー', [
                'user_id' => $user->id,
                'group_task_id' => $groupTaskId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'グループタスクの取得に失敗しました。',
            ], 500);
        }
    }
}
