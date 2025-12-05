<?php

namespace App\Http\Requests\Api\Tags;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * API: タグ更新リクエスト
 */
class UpdateTagApiRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    /**
     * カスタムエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'name.required' => 'タグ名は必須です。',
            'name.max' => 'タグ名は255文字以内で入力してください。',
            'color.regex' => '色コードは#から始まる6桁の16進数で指定してください（例: #FF5733）。',
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
