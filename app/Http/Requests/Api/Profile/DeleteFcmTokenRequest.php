<?php

namespace App\Http\Requests\Api\Profile;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FCMトークン削除リクエスト
 * 
 * @property-read string $device_token
 */
class DeleteFcmTokenRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを実行する権限があるかを判定
     */
    public function authorize(): bool
    {
        return true; // 認証はmiddlewareで制御
    }

    /**
     * バリデーションルール
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'device_token' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * バリデーションエラーメッセージのカスタマイズ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'device_token.required' => 'デバイストークンは必須です。',
            'device_token.string' => 'デバイストークンは文字列で指定してください。',
            'device_token.max' => 'デバイストークンは255文字以内で指定してください。',
        ];
    }
}
