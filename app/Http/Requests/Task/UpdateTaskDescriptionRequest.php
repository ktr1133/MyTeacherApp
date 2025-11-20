<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

/**
 * タスク説明文更新リクエスト
 */
class UpdateTaskDescriptionRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを実行する権限があるか判定
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true; // 権限チェックはサービス層で実施
    }

    /**
     * バリデーションルール
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => 'nullable|string|max:500',
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
            'description.string' => '説明文は文字列で入力してください。',
            'description.max' => '説明文は500文字以内で入力してください。',
        ];
    }

    /**
     * バリデーション対象の属性名
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'description' => '説明文',
        ];
    }
}