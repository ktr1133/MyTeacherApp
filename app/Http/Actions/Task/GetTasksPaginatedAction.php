<?php

namespace App\Http\Actions\Task;

use App\Services\Task\TaskListServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Web版: 無限スクロール用タスク一覧取得アクション
 * 
 * dashboard画面の無限スクロール機能で使用
 * セッション認証（auth middleware）を使用
 */
class GetTasksPaginatedAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected TaskListServiceInterface $taskListService
    ) {}

    /**
     * ページネーションされたタスク一覧を取得
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            // 認証済みユーザーを取得（セッション認証）
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'ユーザー認証に失敗しました。',
                ], 401);
            }

            // リクエストパラメータ取得
            $page = (int) $request->input('page', 1);
            $perPage = (int) $request->input('per_page', 50);
            $filters = $request->only(['search', 'priority', 'tags']);

            // ページ番号のバリデーション
            if ($page < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'ページ番号は1以上を指定してください。',
                ], 422);
            }

            // 1ページあたりの件数のバリデーション
            if ($perPage < 1 || $perPage > 100) {
                return response()->json([
                    'success' => false,
                    'message' => '1ページあたりの件数は1〜100の範囲で指定してください。',
                ], 422);
            }

            // タスク一覧取得
            $result = $this->taskListService->getTasksForUserPaginated(
                $user->id,
                $filters,
                $page,
                $perPage
            );

            // タスクデータの整形
            $tasks = $result['tasks']->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'due_date' => $task->due_date,
                    'span' => $task->span,
                    'priority' => $task->priority,
                    'is_completed' => $task->is_completed,
                    'completed_at' => $task->completed_at?->toIso8601String(),
                    'group_task_id' => $task->group_task_id,
                    'reward' => $task->reward,
                    'requires_approval' => $task->requires_approval,
                    'requires_image' => $task->requires_image,
                    'approved_at' => $task->approved_at?->toIso8601String(),
                    'created_at' => $task->created_at->toIso8601String(),
                    'updated_at' => $task->updated_at->toIso8601String(),
                    'tags' => $task->tags->map(fn($tag) => [
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ]),
                    'images' => $task->images->map(fn($image) => [
                        'id' => $image->id,
                        'url' => $image->url,
                    ]),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'tasks' => $tasks,
                    'pagination' => [
                        'current_page' => $result['current_page'],
                        'next_page' => $result['next_page'],
                        'has_more' => $result['has_more'],
                        'per_page' => $perPage,
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Web: タスク一覧取得エラー（ページネーション）', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()?->id,
                'page' => $request->input('page'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'タスク一覧の取得に失敗しました。',
            ], 500);
        }
    }
}
