<?php

namespace App\Http\Requests\Api\Group;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * API: グループメンバー追加リクエスト
 */
class AddMemberApiRequest extends FormRequest
{
    /**
     * リクエストが認可されているか判定
     */
    public function authorize(): bool
    {
        return true; // Cognito認証ミドルウェアで制御
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
                'string',
                'min:8',
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
                'boolean',
            ],
            'terms_consent' => [
                'required',
                'boolean',
            ],
        ];
    }

    /**
     * カスタムエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'username.required' => 'ユーザー名は必須です。',
            'username.regex' => 'ユーザー名は半角英数字、ハイフン、アンダースコアのみ使用できます。',
            'username.unique' => 'このユーザー名は既に使用されています。',
            'email.required' => 'メールアドレスは必須です。',
            'email.email' => '有効なメールアドレスを入力してください。',
            'email.unique' => 'このメールアドレスは既に使用されています。',
            'password.required' => 'パスワードは必須です。',
            'password.min' => 'パスワードは8文字以上で入力してください。',
            // 代理同意
            'privacy_policy_consent.required' => 'プライバシーポリシーへの同意が必要です。',
            'privacy_policy_consent.boolean' => 'プライバシーポリシー同意の値が不正です。',
            'terms_consent.required' => '利用規約への同意が必要です。',
            'terms_consent.boolean' => '利用規約同意の値が不正です。',
        ];
    }

    /**
     * バリデーション失敗時のJSON応答
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => '入力内容に誤りがあります。',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
