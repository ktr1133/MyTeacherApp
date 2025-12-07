<?php

namespace App\Http\Actions\Api\Task;

use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * タスク詳細取得APIアクション
 * 
 * GET /api/tasks/{task}
 */
class ShowTaskApiAction
{
    /**
     * タスク詳細を取得
     * 
     * @param Request $request
     * @param Task $task - ルートモデルバインディング
     * @return JsonResponse
     */
    public function __invoke(Request $request, Task $task): JsonResponse
    {
        // 認証ユーザー取得
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'ユーザー認証に失敗しました。',
            ], 401);
        }

        // アクセス権限チェック（自分のタスクまたは同じグループ）
        if ($task->user_id !== $user->id && $task->user->group_id !== $user->group_id) {
            return response()->json([
                'success' => false,
                'message' => 'このタスクにアクセスする権限がありません。',
            ], 403);
        }

        // タスク詳細を取得（with関連データ）
        $task->load(['tags', 'images', 'user']);

        // レスポンス（IndexTaskApiActionと同じ形式）
        return response()->json([
            'success' => true,
            'data' => [
                'task' => [
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
                        'color' => $tag->color,
                    ])->toArray(),
                    'images' => $task->images->map(fn($img) => [
                        'id' => $img->id,
                        'file_path' => $img->file_path,
                        'url' => \Storage::disk('s3')->url($img->file_path),
                    ])->toArray(),
                    'created_at' => $task->created_at->toIso8601String(),
                    'updated_at' => $task->updated_at->toIso8601String(),
                ],
            ],
        ]);
    }
}
