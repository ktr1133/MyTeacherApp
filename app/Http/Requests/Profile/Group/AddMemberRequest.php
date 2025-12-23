<?php

namespace App\Http\Requests\Profile\Group;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

/**
 * グループメンバー追加リクエスト
 * 
 * 新規ユーザーとしてusername, email, password, nameを受け取る
 */
class AddMemberRequest extends FormRequest
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
        return [
            'username' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_-]+$/',
                'unique:users,username',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                Rules\Password::min(8)
                    ->letters()      // 英字必須
                    ->mixedCase()    // 大文字小文字必須
                    ->numbers()      // 数字必須
                    ->symbols()      // 記号必須
                    ->uncompromised(), // 漏洩パスワードチェック
            ],
            'name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'group_edit_flg' => [
                'nullable',
                'boolean',
            ],
            // 代理同意（保護者による同意）
            'privacy_policy_consent' => [
                'required',
                'accepted',
            ],
            'terms_consent' => [
                'required',
                'accepted',
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
            
            // password
            'password.required' => 'パスワードは必須です。',
            
            // name
            'name.max' => '表示名は255文字以内で入力してください。',
            
            // group_edit_flg
            'group_edit_flg.boolean' => 'グループ編集権限の値が不正です。',
            
            // 代理同意
            'privacy_policy_consent.required' => 'プライバシーポリシーへの同意が必要です。',
            'privacy_policy_consent.accepted' => 'プライバシーポリシーに同意してください。',
            'terms_consent.required' => '利用規約への同意が必要です。',
            'terms_consent.accepted' => '利用規約に同意してください。',
        ];
    }
}
