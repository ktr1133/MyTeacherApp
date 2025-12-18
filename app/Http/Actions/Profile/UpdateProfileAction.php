<?php

namespace App\Http\Actions\Profile;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Responders\Profile\ProfileResponder;
use App\Repositories\Profile\ProfileUserRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateProfileAction
{
    public function __construct(
        private ProfileResponder $responder,
        private ProfileUserRepositoryInterface $userRepository
    ) {}

    /**
     * プロフィール更新処理
     * - username, email, name を更新（email, username は自分以外と重複不可）
     * - メールアドレス変更時はemail_verified_atをnullにする
     * - 任意で avatar 画像を保存（public/avatars）
     * - 成功/失敗に応じて元画面へリダイレクト
     */
    public function __invoke(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
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

        // メールアドレス変更チェック
        $emailChanged = $user->email !== $validated['email'];
        
        // メールアドレスが変更された場合は検証状態をリセット（カラムが存在する場合のみ）
        if ($emailChanged && Schema::hasColumn('users', 'email_verified_at')) {
            $user->email_verified_at = null;
        }

        // 反映（存在するカラムだけ代入する想定）
        $user->username = $validated['username'];
        $user->email = $validated['email'];
        
        // name が空の場合は username を使用
        $user->name = !empty($validated['name']) ? $validated['name'] : $validated['username'];
        
        if (array_key_exists('avatar_path', $validated)) {
            $user->avatar_path = $validated['avatar_path'];
        }
        
        // bio を users で管理している場合のみ有効化
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
                    Log::info('子ユーザーのparent_email更新完了', [
                        'parent_user_id' => $user->id,
                        'new_email' => $validated['email'],
                        'updated_count' => $updatedCount,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('子ユーザーのparent_email更新失敗', [
                    'parent_user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // 親ユーザーの更新は成功しているため、エラーをログに記録するのみ
            }
        }

        // Responder を通してリダイレクト
        return $this->responder->respondUpdateSuccess();
    }
}