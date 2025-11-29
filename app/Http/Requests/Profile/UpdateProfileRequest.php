<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * プロフィール更新リクエスト
 * 
 * email, name, usernameの更新をサポート
 * 自己除外により自分の既存値は許可
 */
class UpdateProfileRequest extends FormRequest
{
    /**
     * リクエストが認可されているか判定
     */
    public function authorize(): bool
    {
        return true; // 認証済みユーザーのみアクセス可能（ミドルウェアで制御）
    }

    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'username' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_-]+$/',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'bio' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'avatar' => [
                'nullable',
                'image',
                'max:2048', // 2MB
            ],
        ];
    }

    /**
     * カスタムエラーメッセージ
     */
    public function messages(): array
    {
        return [
            // username
            'username.required' => 'ユーザー名は必須です。',
            'username.max' => 'ユーザー名は255文字以内で入力してください。',
            'username.regex' => 'ユーザー名は半角英数字、ハイフン、アンダースコアのみ使用できます。',
            'username.unique' => 'このユーザー名は既に使用されています。',
            
            // email
            'email.required' => 'メールアドレスは必須です。',
            'email.email' => '有効なメールアドレスを入力してください。',
            'email.max' => 'メールアドレスは255文字以内で入力してください。',
            'email.unique' => 'このメールアドレスは既に使用されています。',
            
            // name
            'name.max' => '表示名は255文字以内で入力してください。',
            
            // bio
            'bio.max' => '自己紹介は1000文字以内で入力してください。',
            
            // avatar
            'avatar.image' => 'アバター画像は画像ファイルである必要があります。',
            'avatar.max' => 'アバター画像のサイズは2MB以内にしてください。',
        ];
    }
}
