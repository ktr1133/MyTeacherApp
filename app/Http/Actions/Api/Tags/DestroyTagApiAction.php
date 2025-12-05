<?php

namespace App\Http\Actions\Api\Tags;

use App\Services\Tag\TagServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * API: タグ削除アクション
 * 
 * 既存のタグを削除
 * Cognito認証を前提（middleware: cognito）
 */
class DestroyTagApiAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected TagServiceInterface $tagService
    ) {}

    /**
     * タグを削除
     *
     * @param Request $request
     * @param int $id タグID
     * @return JsonResponse
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'ユーザー認証に失敗しました。',
                ], 401);
            }

            // バリデーション
            $validator = Validator::make(['id' => $id], [
                'id' => ['required', 'integer', 'exists:tags,id'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => '指定されたタグが見つかりません。',
                    'errors' => $validator->errors(),
                ], 404);
            }

            $validated = $validator->validated();

            // タグ削除
            $result = $this->tagService->deleteTag($validated['id']);

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'タグの削除に失敗しました。',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'タグを削除しました。',
                'data' => [
                    'deleted_tag_id' => $id,
                ],
            ], 200);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (\Exception $e) {
            Log::error('タグ削除エラー', [
                'user_id' => $request->user()?->id,
                'tag_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'タグの削除に失敗しました。',
            ], 500);
        }
    }
}
