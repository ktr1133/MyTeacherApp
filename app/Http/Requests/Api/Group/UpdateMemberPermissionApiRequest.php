<?php

namespace App\Http\Requests\Api\Group;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * API: メンバー権限更新リクエスト
 */
class UpdateMemberPermissionApiRequest extends FormRequest
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
            'group_edit_flg' => ['required', 'boolean'],
        ];
    }

    /**
     * カスタムエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'group_edit_flg.required' => '編集権限の設定は必須です。',
            'group_edit_flg.boolean' => '編集権限の値が不正です。',
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
