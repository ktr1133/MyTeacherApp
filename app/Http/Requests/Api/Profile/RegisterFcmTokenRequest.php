<?php

namespace App\Http\Requests\Api\Profile;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FCMトークン登録リクエスト
 * 
 * @property-read string $device_token
 * @property-read string $device_type
 * @property-read string|null $device_name
 * @property-read string|null $app_version
 */
class RegisterFcmTokenRequest extends FormRequest
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
            'device_type' => ['required', 'in:ios,android'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'app_version' => ['nullable', 'string', 'max:20'],
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
            'device_type.required' => 'デバイスタイプは必須です。',
            'device_type.in' => 'デバイスタイプはiosまたはandroidで指定してください。',
            'device_name.string' => 'デバイス名は文字列で指定してください。',
            'device_name.max' => 'デバイス名は100文字以内で指定してください。',
            'app_version.string' => 'アプリバージョンは文字列で指定してください。',
            'app_version.max' => 'アプリバージョンは20文字以内で指定してください。',
        ];
    }
}
