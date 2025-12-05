<?php

namespace App\Http\Actions\Api\Group;

use App\Models\User;
use App\Repositories\Profile\GroupUserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: グループメンバーテーマ切替アクション
 * 
 * グループメンバーのダークモード/ライトモードを切替
 * Cognito認証を前提（middleware: cognito）
 */
class ToggleMemberThemeApiAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected GroupUserRepositoryInterface $groupUserRepository
    ) {}

    /**
     * メンバーのテーマを切替
     *
     * @param Request $request
     * @param int $memberId メンバーID
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

            // 対象メンバーを取得
            $member = User::find($memberId);
            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'メンバーが見つかりません。',
                ], 404);
            }

            // 権限チェック: 自分自身またはグループ編集権限が必要
            if ($user->id !== $member->id && !$user->group_edit_flg) {
                return response()->json([
                    'success' => false,
                    'message' => 'テーマを変更する権限がありません。',
                ], 403);
            }

            // 同じグループに属しているか確認
            if ($user->group_id !== $member->group_id) {
                return response()->json([
                    'success' => false,
                    'message' => '他のグループのメンバーは変更できません。',
                ], 403);
            }

            // テーマ切替（adult ↔ child）
            $newTheme = $member->theme === 'adult' ? 'child' : 'adult';
            $this->groupUserRepository->update($member, ['theme' => $newTheme]);

            return response()->json([
                'success' => true,
                'message' => 'テーマを切り替えました。',
                'data' => [
                    'member' => [
                        'id' => $member->id,
                        'theme' => $newTheme,
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('テーマ切替エラー', [
                'user_id' => $request->user()?->id,
                'member_id' => $memberId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'テーマの切替に失敗しました。',
            ], 500);
        }
    }
}
