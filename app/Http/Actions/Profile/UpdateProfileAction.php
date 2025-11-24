<?php

namespace App\Http\Actions\Profile;

use App\Responders\Profile\ProfileResponder;
use Illuminate\Http\Request;
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
     * - name, email を更新（email は自分以外と重複不可）
     * - 任意で avatar 画像を保存（public/avatars）
     * - 成功/失敗に応じて元画面へリダイレクト
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'email'  => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            // 任意項目（DBにカラムがある場合のみ有効にしてください）
            'bio'    => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'image', 'max:2048'], // 2MB
        ]);

        // 画像アップロード（任意）
        if ($request->hasFile('avatar')) {
            // 既存の画像があれば削除
            if (!empty($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar_path'] = $path;
        }

        // 反映（存在するカラムだけ代入する想定）
        $user->name  = $validated['name'];
        $user->email = $validated['email'];
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