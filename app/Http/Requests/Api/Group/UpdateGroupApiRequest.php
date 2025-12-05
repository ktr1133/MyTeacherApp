<?php

namespace App\Http\Requests\Api\Group;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * API: グループ情報更新リクエスト
 */
class UpdateGroupApiRequest extends FormRequest
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
        ];
    }

    /**
     * カスタムエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'name.required' => 'グループ名は必須です。',
            'name.max' => 'グループ名は255文字以内で入力してください。',
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
