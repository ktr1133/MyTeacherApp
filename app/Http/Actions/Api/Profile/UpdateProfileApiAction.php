<?php

namespace App\Http\Actions\Api\Profile;

use App\Http\Requests\Api\Profile\UpdateProfileApiRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

/**
 * API: プロフィール更新アクション
 * 
 * ユーザーのプロフィール情報を更新
 * Cognito認証を前提（middleware: cognito）
 */
class UpdateProfileApiAction
{
    /**
     * プロフィール情報を更新
     *
     * @param UpdateProfileApiRequest $request
     * @return JsonResponse
     */
    public function __invoke(UpdateProfileApiRequest $request): JsonResponse
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

            // 画像アップロード（任意）
            if ($request->hasFile('avatar')) {
                // 既存の画像があれば削除
                if (!empty($user->avatar_path)) {
                    Storage::disk('public')->delete($user->avatar_path);
                }
                $path = $request->file('avatar')->store('avatars', 'public');
                $validated['avatar_path'] = $path;
            }

            // 更新処理
            if (array_key_exists('username', $validated)) {
                $user->username = $validated['username'];
            }
            if (array_key_exists('email', $validated)) {
                $user->email = $validated['email'];
            }
            if (array_key_exists('name', $validated)) {
                $user->name = !empty($validated['name']) ? $validated['name'] : $user->username;
            }
            if (array_key_exists('avatar_path', $validated)) {
                $user->avatar_path = $validated['avatar_path'];
            }
            if (array_key_exists('bio', $validated) && Schema::hasColumn('users', 'bio')) {
                $user->bio = $validated['bio'];
            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'プロフィールを更新しました。',
                'data' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_path' => $user->avatar_path,
                    'bio' => $user->bio ?? null,
                    'updated_at' => $user->updated_at->toIso8601String(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('プロフィール更新エラー', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'プロフィールの更新に失敗しました。',
            ], 500);
        }
    }
}
