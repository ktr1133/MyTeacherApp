<?php

namespace App\Http\Actions\Profile;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Responders\Profile\ProfileResponder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class UpdateProfileAction
{
    public function __construct(
        private ProfileResponder $responder
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

        // メールアドレスが変更された場合は検証状態をリセット
        if ($user->email !== $validated['email']) {
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

        // Responder を通してリダイレクト
        return $this->responder->respondUpdateSuccess();
    }
}