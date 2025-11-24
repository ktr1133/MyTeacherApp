<?php

namespace App\Http\Requests\Admin\Portal;

use Illuminate\Foundation\Http\FormRequest;

/**
 * メンテナンス情報ステータス更新リクエスト
 */
class UpdateMaintenanceStatusRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを実行する権限があるか判定
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->is_admin;
    }

    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:scheduled,in_progress,completed'],
        ];
    }

    /**
     * バリデーションエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'status.required' => 'ステータスは必須です。',
            'status.in' => '無効なステータスが指定されています。',
        ];
    }
}
