<?php

namespace App\Http\Actions\Api\Tags;

use App\Services\Tag\TagServiceInterface;
use App\Http\Requests\Api\Tags\UpdateTagApiRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * API: タグ更新アクション
 * 
 * 既存のタグ情報を更新
 * Cognito認証を前提（middleware: cognito）
 */
class UpdateTagApiAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected TagServiceInterface $tagService
    ) {}

    /**
     * タグを更新
     *
     * @param UpdateTagApiRequest $request
     * @param int $id タグID
     * @return JsonResponse
     */
    public function __invoke(UpdateTagApiRequest $request, int $id): JsonResponse
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
            $validated['id'] = $id;

            // タグ更新
            $tag = $this->tagService->updateTag($user, $validated);

            return response()->json([
                'success' => true,
                'message' => 'タグを更新しました。',
                'data' => [
                    'tag' => [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'color' => $tag->color ?? '#3B82F6',
                        'updated_at' => $tag->updated_at->toIso8601String(),
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('タグ更新エラー', [
                'user_id' => $request->user()?->id,
                'tag_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'タグの更新に失敗しました。',
            ], 500);
        }
    }
}
