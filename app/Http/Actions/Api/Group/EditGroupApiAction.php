<?php

namespace App\Http\Actions\Api\Group;

use App\Services\Profile\GroupServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: グループ情報取得アクション
 * 
 * グループの詳細情報とメンバー一覧を取得
 * Cognito認証を前提（middleware: cognito）
 */
class EditGroupApiAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected GroupServiceInterface $groupService
    ) {}

    /**
     * グループ情報とメンバー一覧を取得
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

            [$group, $members] = $this->groupService->getEditData($user);

            if (!$group) {
                return response()->json([
                    'success' => false,
                    'message' => 'グループが見つかりません。',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'group' => [
                        'id' => $group->id,
                        'name' => $group->name,
                        'master_user_id' => $group->master_user_id,
                        'created_at' => $group->created_at->toIso8601String(),
                        'updated_at' => $group->updated_at->toIso8601String(),
                    ],
                    'members' => $members->map(function ($member) {
                        return [
                            'id' => $member->id,
                            'username' => $member->username,
                            'name' => $member->name,
                            'email' => $member->email,
                            'group_edit_flg' => (bool) $member->group_edit_flg,
                            'is_master' => $member->group->master_user_id === $member->id,
                        ];
                    })->values(),
                ],
            ], 200);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (\Exception $e) {
            Log::error('グループ情報取得エラー', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'グループ情報の取得に失敗しました。',
            ], 500);
        }
    }
}
