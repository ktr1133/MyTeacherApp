<?php

namespace App\Http\Requests\Profile\Group;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 紐付けリクエスト送信リクエスト
 * 
 * 子アカウントIDのバリデーションを実行します。
 */
class SendChildLinkRequestRequest extends FormRequest
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
            'child_user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
        ];
    }

    /**
     * バリデーションエラーメッセージをカスタマイズ     * 
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'child_user_id.required' => '子アカウントIDが指定されていません。',
            'child_user_id.integer' => '子アカウントIDは整数である必要があります。',
            'child_user_id.exists' => '指定された子アカウントが存在しません。',
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
            'child_user_id' => '子アカウントID',
        ];
    }
}
