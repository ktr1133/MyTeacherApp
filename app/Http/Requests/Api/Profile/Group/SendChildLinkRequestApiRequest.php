<?php

namespace App\Http\Requests\Api\Profile\Group;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 紐付けリクエスト送信APIリクエスト
 * 
 * モバイルアプリ用: 子アカウントIDのバリデーションを実施します。
 */
class SendChildLinkRequestApiRequest extends FormRequest
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
            'child_user_id' => ['required', 'integer', 'exists:users,id'],
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
            'child_user_id.required' => '子アカウントIDを指定してください。',
            'child_user_id.integer' => '子アカウントIDは整数で指定してください。',
            'child_user_id.exists' => '指定された子アカウントが見つかりません。',
        ];
    }
}
