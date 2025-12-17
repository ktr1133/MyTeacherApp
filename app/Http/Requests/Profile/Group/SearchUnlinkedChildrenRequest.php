<?php

namespace App\Http\Requests\Profile\Group;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 未紐付け子アカウント検索リクエスト
 * 
 * 保護者のメールアドレスでバリデーションを実行します。
 */
class SearchUnlinkedChildrenRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを実行する権限があるか判定
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        // 認証済みユーザーのみ実行可能
        return $this->user() !== null;
    }

    /**
     * バリデーションルールを取得
     * 
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'parent_email' => [
                'required',
                'email:rfc',
                'max:255',
            ],
        ];
    }

    /**
     * バリデーションエラーメッセージをカスタマイズ
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

    /**
     * バリデーション属性名をカスタマイズ
     * 
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'parent_email' => '保護者のメールアドレス',
        ];
    }
}
