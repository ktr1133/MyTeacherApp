<?php

namespace App\Http\Actions\Api\GroupTask;

use App\Services\Task\TaskManagementServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * グループタスク一覧取得API Action
 * 
 * モバイルアプリ用: ログインユーザーが編集可能なグループタスクの一覧を返却
 */
class IndexGroupTaskApiAction
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
     * グループタスク一覧を取得
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // 権限チェック
        if (!$user->canEditGroup()) {
            return response()->json([
                'message' => 'この操作を実行する権限がありません。',
            ], 403);
        }

        try {
            // グループタスク取得（group_task_id単位でグループ化）
            $groupTasks = $this->taskManagementService->getEditableGroupTasks($user);
            
            Log::info('[API] グループタスク一覧取得', [
                'user_id' => $user->id,
                'count' => $groupTasks->count(),
            ]);

            // レスポンス整形（$groupTasksは配列のコレクション）
            $data = $groupTasks->map(function ($task) {
                // due_dateは文字列（長期）またはCarbonインスタンス（短期・中期）の可能性がある
                $dueDate = $task['due_date'];
                if ($dueDate instanceof \Carbon\Carbon) {
                    $dueDate = $dueDate->format('Y-m-d');
                }
                
                return [
                    'group_task_id' => $task['group_task_id'],
                    'title' => $task['title'],
                    'description' => $task['description'],
                    'span' => $task['span'],
                    'reward' => $task['reward'],
                    'due_date' => $dueDate,
                    'requires_approval' => (bool) $task['requires_approval'],
                    'requires_image' => (bool) $task['requires_image'],
                    'assigned_count' => $task['assigned_count'] ?? 0,
                    'created_at' => $task['created_at']?->toIso8601String(),
                    'updated_at' => $task['updated_at']?->toIso8601String(),
                ];
            });

            return response()->json($data);
            
        } catch (\Exception $e) {
            Log::error('[API] グループタスク一覧取得エラー', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'グループタスクの取得に失敗しました。',
            ], 500);
        }
    }
}
