<?php

namespace App\Http\Actions\Api\Group;

use App\Services\Profile\GroupServiceInterface;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: グループマスター譲渡アクション
 * 
 * グループマスター権限を別のメンバーに譲渡
 * Cognito認証を前提（middleware: cognito）
 */
class TransferGroupMasterApiAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected GroupServiceInterface $groupService
    ) {}

    /**
     * グループマスターを譲渡
     *
     * @param Request $request
     * @param int $newMasterId 新しいマスターのユーザーID
     * @return JsonResponse
     */
    public function __invoke(Request $request, int $newMasterId): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'ユーザー認証に失敗しました。',
                ], 401);
            }

            // 新しいマスターを取得
            $newMaster = User::find($newMasterId);
            if (!$newMaster) {
                return response()->json([
                    'success' => false,
                    'message' => '譲渡先のメンバーが見つかりません。',
                ], 404);
            }

            $this->groupService->transferMaster($user, $newMaster);

            return response()->json([
                'success' => true,
                'message' => 'グループマスターを譲渡しました。',
                'data' => [
                    'new_master' => [
                        'id' => $newMaster->id,
                        'username' => $newMaster->username,
                        'name' => $newMaster->name,
                    ],
                ],
            ], 200);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (\Exception $e) {
            Log::error('グループマスター譲渡エラー', [
                'user_id' => $request->user()?->id,
                'new_master_id' => $newMasterId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'グループマスターの譲渡に失敗しました。',
            ], 500);
        }
    }
}
