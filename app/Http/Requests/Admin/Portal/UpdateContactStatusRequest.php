<?php

namespace App\Http\Requests\Admin\Portal;

use Illuminate\Foundation\Http\FormRequest;

/**
 * お問い合わせステータス更新リクエスト
 */
class UpdateContactStatusRequest extends FormRequest
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
            'status' => ['required', 'string', 'in:pending,in_progress,resolved'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
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
            'admin_note.max' => '管理者メモは1000文字以内で入力してください。',
        ];
    }
}
