<?php

namespace App\Http\Requests\Admin\Portal;

use Illuminate\Foundation\Http\FormRequest;

/**
 * メンテナンス情報更新リクエスト
 */
class UpdateMaintenanceRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'scheduled_at' => ['required', 'date'],
            'estimated_duration' => ['required', 'integer', 'min:1'],
            'affected_apps' => ['required', 'array', 'min:1'],
            'affected_apps.*' => ['string', 'in:myteacher,app2,app3'],
            'status' => ['nullable', 'string', 'in:scheduled,in_progress,completed'],
        ];
    }

    /**
     * バリデーションエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'title.required' => 'タイトルは必須です。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'description.required' => '詳細説明は必須です。',
            'scheduled_at.required' => 'メンテナンス予定日時は必須です。',
            'scheduled_at.date' => '有効な日時を入力してください。',
            'estimated_duration.required' => '予定時間は必須です。',
            'estimated_duration.integer' => '予定時間は整数で入力してください。',
            'estimated_duration.min' => '予定時間は1分以上を指定してください。',
            'affected_apps.required' => '対象アプリは必須です。',
            'affected_apps.min' => '対象アプリを1つ以上選択してください。',
            'affected_apps.*.in' => '無効なアプリが指定されています。',
            'status.in' => '無効なステータスが指定されています。',
        ];
    }
}
