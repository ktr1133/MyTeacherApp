<?php

namespace App\Http\Actions\Api\Task;

use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * モバイルアプリ用タスク一覧取得API Action
 * 
 * ログインユーザーのタスク一覧をJSON形式で返却
 */
class IndexTaskApiAction
{
    /**
     * タスク一覧取得API
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $tasks = Task::where('user_id', $user->id)
                ->with(['images', 'tags', 'user'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'tasks' => $tasks->items(),
                    'pagination' => [
                        'current_page' => $tasks->currentPage(),
                        'last_page' => $tasks->lastPage(),
                        'per_page' => $tasks->perPage(),
                        'total' => $tasks->total(),
                    ],
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                    'version' => 'v1',
                ],
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Task index failed via API', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TASK_INDEX_FAILED',
                    'message' => 'タスク一覧の取得に失敗しました。',
                ],
            ], 500);
        }
    }
}
