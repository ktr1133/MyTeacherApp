<?php

namespace App\Http\Actions\Api\Group;

use App\Services\Profile\GroupServiceInterface;
use App\Http\Requests\Api\Group\UpdateMemberPermissionApiRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * API: グループメンバー権限更新アクション
 * 
 * グループメンバーの編集権限を変更
 * Cognito認証を前提（middleware: cognito）
 */
class UpdateMemberPermissionApiAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected GroupServiceInterface $groupService
    ) {}

    /**
     * メンバーの編集権限を更新
     *
     * @param UpdateMemberPermissionApiRequest $request
     * @param int $memberId メンバーID
     * @return JsonResponse
     */
    public function __invoke(UpdateMemberPermissionApiRequest $request, int $memberId): JsonResponse
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

            // 対象メンバーを取得
            $member = User::find($memberId);
            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'メンバーが見つかりません。',
                ], 404);
            }

            $this->groupService->updateMemberPermission($user, $member, $validated['group_edit_flg']);

            return response()->json([
                'success' => true,
                'message' => 'メンバーの権限を更新しました。',
                'data' => [
                    'member' => [
                        'id' => $member->id,
                        'group_edit_flg' => (bool) $validated['group_edit_flg'],
                    ],
                ],
            ], 200);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (\Exception $e) {
            Log::error('メンバー権限更新エラー', [
                'user_id' => $request->user()?->id,
                'member_id' => $memberId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'メンバー権限の更新に失敗しました。',
            ], 500);
        }
    }
}
