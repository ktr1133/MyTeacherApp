<?php

namespace App\Http\Actions\Api\Task;

use App\Http\Requests\Task\StoreTaskRequest;
use App\Services\Task\TaskManagementServiceInterface;
use Illuminate\Http\JsonResponse;

/**
 * モバイルアプリ用タスク作成API Action
 * 
 * 既存TaskManagementServiceを活用し、マイクロサービス削除後の
 * モバイルAPI統合を実現
 */
class StoreTaskApiAction
{
    public function __construct(
        protected TaskManagementServiceInterface $taskService
    ) {}
    
    /**
     * タスク作成API
     * 
     * @param StoreTaskRequest $request バリデーション済みリクエスト
     * @return JsonResponse
     */
    public function __invoke(StoreTaskRequest $request): JsonResponse
    {
        try {
            $task = $this->taskService->createTask(
                $request->user(),
                $request->validated()
            );
            
            return response()->json([
                'success' => true,
                'data' => [
                    'task' => $task->load(['images', 'tags', 'user']),
                ],
                'message' => 'タスクが作成されました。',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                    'version' => 'v1',
                ],
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Task creation failed via API', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TASK_CREATION_FAILED',
                    'message' => 'タスクの作成に失敗しました。',
                    'details' => config('app.debug') ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }
}
