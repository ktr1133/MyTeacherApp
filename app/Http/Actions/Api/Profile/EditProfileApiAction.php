<?php

namespace App\Http\Actions\Api\Profile;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: プロフィール取得アクション
 * 
 * 認証済みユーザーのプロフィール情報を取得
 * Cognito認証を前提（middleware: cognito）
 */
class EditProfileApiAction
{
    /**
     * プロフィール情報を取得
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

            // groupリレーションをload
            $user->load('group');

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_path' => $user->avatar_path,
                    'bio' => $user->bio ?? null,
                    'timezone' => $user->timezone ?? 'Asia/Tokyo',
                    'theme' => $user->theme ?? 'light',
                    'group_id' => $user->group_id,
                    'group_edit_flg' => (bool) $user->group_edit_flg,
                    'group' => $user->group ? [
                        'id' => $user->group->id,
                        'name' => $user->group->name,
                    ] : null,
                    'auth_provider' => $user->auth_provider ?? 'breeze',
                    'cognito_sub' => $user->cognito_sub,
                    'created_at' => $user->created_at->toIso8601String(),
                    'updated_at' => $user->updated_at->toIso8601String(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('プロフィール取得エラー', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'プロフィール情報の取得に失敗しました。',
            ], 500);
        }
    }
}
