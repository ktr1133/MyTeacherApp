<?php

namespace App\Http\Requests\Api\Report;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * メンバー別概況レポート生成リクエスト
 */
class GenerateMemberSummaryRequest extends FormRequest
{
    /**
     * リクエストが許可されているか判定
     */
    public function authorize(): bool
    {
        return true; // Actionで権限チェック
    }

    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'group_id' => ['required', 'integer', 'exists:groups,id'],
            'year_month' => ['required', 'string', 'date_format:Y-m'],
        ];
    }

    /**
     * バリデーションメッセージ
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'ユーザーIDは必須です。',
            'user_id.exists' => '指定されたユーザーが存在しません。',
            'group_id.required' => 'グループIDは必須です。',
            'group_id.exists' => '指定されたグループが存在しません。',
            'year_month.required' => '対象年月は必須です。',
            'year_month.date_format' => '対象年月はYYYY-MM形式で指定してください。',
        ];
    }

    /**
     * バリデーション失敗時の処理（API用）
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'バリデーションエラー',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
