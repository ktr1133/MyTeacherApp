<?php

namespace App\Http\Actions\Api\Task;

use App\Models\Task;
use App\Services\Task\TaskManagementServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * モバイルアプリ用タスク削除API Action
 * 
 * 既存TaskManagementServiceを活用
 */
class DestroyTaskApiAction
{
    public function __construct(
        protected TaskManagementServiceInterface $taskService
    ) {}
    
    /**
     * タスク削除API
     * 
     * @param Request $request
     * @param Task $task
     * @return JsonResponse
     */
    public function __invoke(Request $request, Task $task): JsonResponse
    {
        try {
            // 権限確認
            if ($task->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'このタスクを削除する権限がありません。',
                    ],
                ], 403);
            }
            
            $this->taskService->deleteTask($task);
            
            return response()->json([
                'success' => true,
                'message' => 'タスクが削除されました。',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                    'version' => 'v1',
                ],
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Task deletion failed via API', [
                'task_id' => $task->id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TASK_DELETION_FAILED',
                    'message' => 'タスクの削除に失敗しました。',
                ],
            ], 500);
        }
    }
}
