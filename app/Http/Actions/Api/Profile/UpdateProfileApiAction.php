<?php

namespace App\Http\Actions\Api\Profile;

use App\Http\Requests\Api\Profile\UpdateProfileApiRequest;
use App\Repositories\Profile\ProfileUserRepositoryInterface;
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
    public function __construct(
        private ProfileUserRepositoryInterface $userRepository
    ) {}
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

            // メールアドレス変更チェック
            $emailChanged = array_key_exists('email', $validated) && $user->email !== $validated['email'];

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
                // メールアドレス変更時はemail_verified_atをリセット（カラムが存在する場合）
                if ($emailChanged && Schema::hasColumn('users', 'email_verified_at')) {
                    $user->email_verified_at = null;
                }
                $user->email = $validated['email'];
            }
            if (array_key_exists('name', $validated)) {
                $user->name = !empty($validated['name']) ? $validated['name'] : $user->username;
            }
            if (array_key_exists('theme', $validated)) {
                $user->theme = $validated['theme'];
            }
            if (array_key_exists('avatar_path', $validated)) {
                $user->avatar_path = $validated['avatar_path'];
            }
            if (array_key_exists('bio', $validated) && Schema::hasColumn('users', 'bio')) {
                $user->bio = $validated['bio'];
            }

            $user->save();

            // メールアドレスが変更された場合、子ユーザーのparent_emailも更新
            if ($emailChanged) {
                try {
                    $children = $this->userRepository->getChildrenByParentUserId($user->id);
                    if ($children->isNotEmpty()) {
                        $updatedCount = $this->userRepository->updateChildrenParentEmail($children, $validated['email']);
                        Log::info('子ユーザーのparent_email更新完了（API）', [
                            'parent_user_id' => $user->id,
                            'new_email' => $validated['email'],
                            'updated_count' => $updatedCount,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('子ユーザーのparent_email更新失敗（API）', [
                        'parent_user_id' => $user->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    // 親ユーザーの更新は成功しているため、エラーをログに記録するのみ
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'プロフィールを更新しました。',
                'data' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'email' => $user->email,
                    'theme' => $user->theme,
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
