<?php

namespace App\Http\Actions\Api\Task;

use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * API: タスク一覧取得アクション
 * 
 * モバイルアプリからのタスク一覧取得リクエストを処理
 * Cognito認証を前提（middleware: cognito）
 */
class IndexTaskApiAction
{
    /**
     * タスク一覧を取得
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            // 認証済みユーザーを取得（VerifyCognitoTokenで注入済み）
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'ユーザー認証に失敗しました。',
                ], 401);
            }

            // クエリパラメータ
            $status = $request->query('status'); // 'pending', 'completed'
            $perPage = min((int) $request->query('per_page', 20), 100); // 最大100件
            $page = (int) $request->query('page', 1);

            // タスククエリ
            $query = Task::where('user_id', $user->id)
                ->with(['tags', 'images'])
                ->orderBy('created_at', 'desc');

            // ステータスフィルタ（is_completedカラムを使用）
            if ($status === 'pending') {
                $query->where('is_completed', false);
            } elseif ($status === 'completed') {
                $query->where('is_completed', true);
            }

            // ページネーション
            $tasks = $query->paginate($perPage, ['*'], 'page', $page);

            // レスポンス
            return response()->json([
                'success' => true,
                'data' => [
                    'tasks' => collect($tasks->items())->map(function ($task) {
                        return [
                            'id' => $task->id,
                            'title' => $task->title,
                            'description' => $task->description,
                            'span' => $task->span,
                            'due_date' => $task->hasParsableDueDate() ? $task->due_date->format('Y-m-d') : $task->due_date,
                            'priority' => $task->priority,
                            'is_completed' => $task->is_completed,
                            'completed_at' => $task->completed_at?->toIso8601String(),
                            'approved_at' => $task->approved_at?->toIso8601String(),
                            'reward' => $task->reward,
                            'requires_approval' => $task->requires_approval,
                            'requires_image' => $task->requires_image,
                            'is_group_task' => $task->group_task_id !== null,
                            'group_task_id' => $task->group_task_id,
                            'assigned_by_user_id' => $task->assigned_by_user_id,
                            'tags' => $task->tags->map(fn($tag) => [
                                'id' => $tag->id,
                                'name' => $tag->name,
                            ])->toArray(),
                            'images' => $task->images
                                ->filter(fn($img) => !empty($img->file_path))
                                ->map(fn($img) => [
                                    'id' => $img->id,
                                    'path' => $img->file_path,
                                    'url' => Storage::disk('s3')->url($img->file_path),
                                ])
                                ->values()
                                ->toArray(),
                            'created_at' => $task->created_at->toIso8601String(),
                            'updated_at' => $task->updated_at->toIso8601String(),
                        ];
                    }),
                    'pagination' => [
                        'current_page' => $tasks->currentPage(),
                        'per_page' => $tasks->perPage(),
                        'total' => $tasks->total(),
                        'last_page' => $tasks->lastPage(),
                        'from' => $tasks->firstItem(),
                        'to' => $tasks->lastItem(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('API: タスク一覧取得エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'サーバーエラーが発生しました。',
            ], 500);
        }
    }
}
