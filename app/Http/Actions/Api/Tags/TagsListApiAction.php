<?php

namespace App\Http\Actions\Api\Tags;

use App\Services\Tag\TagServiceInterface;
use App\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: タグ一覧取得アクション
 * 
 * ユーザーに紐づくタグとタスクの一覧を取得
 * モバイルAPI専用: tasks_count付きでタグを返却
 * Sanctum認証を前提（middleware: auth:sanctum）
 */
class TagsListApiAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected TagServiceInterface $tagService,
        protected TagRepositoryInterface $tagRepository
    ) {}

    /**
     * タグとタスクの一覧を取得
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'ユーザー認証に失敗しました。',
                ], 401);
            }

            // タグデータを取得（モバイルAPI用: tasks_count付き）
            $tags = $this->tagRepository->getByUserIdWithTaskCount($user->id);

            // タグに関連付けられたタスクを取得
            $tasks = $this->tagService->getTasksByUserId($user->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'tags' => collect($tags)->map(function ($tag) {
                        return [
                            'id' => $tag->id,
                            'name' => $tag->name,
                            'color' => $tag->color ?? '#3B82F6',
                            'tasks_count' => $tag->tasks_count ?? 0,
                            'created_at' => $tag->created_at->toIso8601String(),
                            'updated_at' => $tag->updated_at->toIso8601String(),
                        ];
                    })->values(),
                    'tasks' => collect($tasks)->map(function ($task) {
                        return [
                            'id' => $task->id,
                            'title' => $task->title,
                            'is_completed' => (bool) $task->is_completed,
                            'tag_id' => $task->pivot->tag_id ?? null,
                        ];
                    })->values(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('タグ一覧取得エラー', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'タグ一覧の取得に失敗しました。',
            ], 500);
        }
    }
}
