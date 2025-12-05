<?php

namespace App\Http\Actions\Api\Tags;

use App\Services\Tag\TagServiceInterface;
use App\Http\Requests\Api\Tags\StoreTagApiRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * API: タグ作成アクション
 * 
 * 新しいタグを作成
 * Cognito認証を前提（middleware: cognito）
 */
class StoreTagApiAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected TagServiceInterface $tagService
    ) {}

    /**
     * タグを作成
     *
     * @param StoreTagApiRequest $request
     * @return JsonResponse
     */
    public function __invoke(StoreTagApiRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'ユーザー認証に失敗しました。',
                ], 401);
            }

            $validated = $request->validated();

            // タグ作成
            $tag = $this->tagService->createTag($user, $validated);

            return response()->json([
                'success' => true,
                'message' => 'タグを作成しました。',
                'data' => [
                    'tag' => [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'color' => $tag->color ?? '#3B82F6',
                        'created_at' => $tag->created_at->toIso8601String(),
                        'updated_at' => $tag->updated_at->toIso8601String(),
                    ],
                    'avatar_event' => config('const.avatar_events.tag_created'),
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('タグ作成エラー', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'タグの作成に失敗しました。',
            ], 500);
        }
    }
}
