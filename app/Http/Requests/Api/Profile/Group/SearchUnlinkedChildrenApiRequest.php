<?php

namespace App\Http\Requests\Api\Profile\Group;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 未紐付け子アカウント検索APIリクエスト
 * 
 * モバイルアプリ用: 保護者のメールアドレスでバリデーションを実施します。
 */
class SearchUnlinkedChildrenApiRequest extends FormRequest
{
    /**
     * リクエストの認可
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        // 認証済みユーザーのみ許可
        return $this->user() !== null;
    }

    /**
     * バリデーションルール
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'parent_email' => ['required', 'email', 'max:255'],
        ];
    }

    /**
     * バリデーションエラーメッセージ
     * 
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'parent_email.required' => '保護者のメールアドレスを入力してください。',
            'parent_email.email' => '有効なメールアドレスを入力してください。',
            'parent_email.max' => 'メールアドレスは255文字以内で入力してください。',
        ];
    }
}
