<?php

namespace App\Http\Requests\Api\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * API: タイムゾーン更新リクエスト
 */
class UpdateTimezoneApiRequest extends FormRequest
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
            'timezone' => ['required', 'string', 'timezone'],
        ];
    }

    /**
     * カスタムエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'timezone.required' => 'タイムゾーンの選択は必須です。',
            'timezone.timezone' => '無効なタイムゾーンが選択されました。',
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
