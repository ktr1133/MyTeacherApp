<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 管理者通知更新リクエスト
 * 
 * 管理者が既存通知を更新する際のバリデーションルールを定義。
 * 
 * @package App\Http\Requests\Notification
 */
class UpdateNotificationRequest extends FormRequest
{
    /**
     * リクエストの認可
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->is_admin;
    }

    /**
     * バリデーションルール
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $notificationId = $this->route('notification');

        return [
            'type' => ['required', 'string', 'max:50'],
            'priority' => ['required', 'in:info,normal,important'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:10000'],
            'data' => ['nullable', 'array'],
            'action_url' => ['nullable', 'url', 'max:255'],
            'action_text' => ['nullable', 'string', 'max:100'],
            'official_page_slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('notification_templates', 'official_page_slug')->ignore($notificationId),
                'regex:/^[a-z0-9\-]+$/'
            ],
            'target_type' => ['required', 'in:all,users,groups'],
            'target_ids' => ['nullable', 'array', 'required_if:target_type,users,groups'],
            'target_ids.*' => ['integer', 'exists:users,id'],
            'publish_at' => ['nullable', 'date'],
            'expire_at' => ['nullable', 'date', 'after:publish_at'],
        ];
    }

    /**
     * カスタムバリデーションメッセージ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => '通知種別を選択してください。',
            'priority.required' => '優先度を選択してください。',
            'priority.in' => '優先度は「情報」「通常」「重要」のいずれかを選択してください。',
            'title.required' => 'タイトルを入力してください。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'message.required' => '本文を入力してください。',
            'message.max' => '本文は10,000文字以内で入力してください。',
            'action_url.url' => 'アクションURLの形式が正しくありません。',
            'official_page_slug.unique' => 'この公式ページスラッグは既に使用されています。',
            'official_page_slug.regex' => '公式ページスラッグは半角英数字とハイフンのみ使用できます。',
            'target_type.required' => '配信対象を選択してください。',
            'target_ids.required_if' => '配信対象を指定してください。',
            'target_ids.*.exists' => '指定されたユーザーまたはグループが存在しません。',
            'expire_at.after' => '公開終了日時は公開開始日時より後を指定してください。',
        ];
    }

    /**
     * バリデーション後のデータ準備
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if ($this->target_type === 'all') {
            $this->merge(['target_ids' => null]);
        }
    }
}