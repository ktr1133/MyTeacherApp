<?php

namespace App\Http\Requests\Api\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * パスワード更新リクエスト（モバイルAPI用）
 * 
 * Web版: PasswordController::update()
 * 対応Action: UpdatePasswordApiAction
 */
class UpdatePasswordRequest extends FormRequest
{
    /**
     * リクエストの認証
     */
    public function authorize(): bool
    {
        // Sanctum認証済みユーザーのみ許可
        return $this->user() !== null;
    }

    /**
     * バリデーションルール
     * 
     * Web版 PasswordController と同じルールを使用
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ];
    }

    /**
     * バリデーションエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'current_password.required' => '現在のパスワードを入力してください',
            'current_password.current_password' => '現在のパスワードが正しくありません',
            'password.required' => '新しいパスワードを入力してください',
            'password.confirmed' => 'パスワードが確認用と一致しません',
        ];
    }
}
