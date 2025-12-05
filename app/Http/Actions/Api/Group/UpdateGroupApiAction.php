<?php

namespace App\Http\Actions\Api\Group;

use App\Services\Profile\GroupServiceInterface;
use App\Http\Requests\Api\Group\UpdateGroupApiRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * API: グループ情報更新アクション
 * 
 * グループ名を更新
 * Cognito認証を前提（middleware: cognito）
 */
class UpdateGroupApiAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected GroupServiceInterface $groupService
    ) {}

    /**
     * グループ情報を更新
     *
     * @param UpdateGroupApiRequest $request
     * @return JsonResponse
     */
    public function __invoke(UpdateGroupApiRequest $request): JsonResponse
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
            
            $avatarEvent = $this->groupService->createOrUpdateGroup($user, $validated['name']);

            return response()->json([
                'success' => true,
                'message' => 'グループ情報を更新しました。',
                'data' => [
                    'avatar_event' => $avatarEvent,
                ],
            ], 200);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (\Exception $e) {
            Log::error('グループ更新エラー', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'グループ情報の更新に失敗しました。',
            ], 500);
        }
    }
}
