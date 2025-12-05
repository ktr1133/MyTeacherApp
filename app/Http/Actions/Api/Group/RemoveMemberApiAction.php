<?php

namespace App\Http\Actions\Api\Group;

use App\Services\Profile\GroupServiceInterface;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: グループメンバー削除アクション
 * 
 * グループからメンバーを削除
 * Cognito認証を前提（middleware: cognito）
 */
class RemoveMemberApiAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected GroupServiceInterface $groupService
    ) {}

    /**
     * グループからメンバーを削除
     *
     * @param Request $request
     * @param int $memberId 削除するメンバーID
     * @return JsonResponse
     */
    public function __invoke(Request $request, int $memberId): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'ユーザー認証に失敗しました。',
                ], 401);
            }

            // 削除対象メンバーを取得
            $member = User::find($memberId);
            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'メンバーが見つかりません。',
                ], 404);
            }

            $this->groupService->removeMember($user, $member);

            return response()->json([
                'success' => true,
                'message' => 'メンバーをグループから削除しました。',
                'data' => [
                    'removed_member_id' => $memberId,
                ],
            ], 200);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (\Exception $e) {
            Log::error('メンバー削除エラー', [
                'user_id' => $request->user()?->id,
                'member_id' => $memberId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'メンバーの削除に失敗しました。',
            ], 500);
        }
    }
}
