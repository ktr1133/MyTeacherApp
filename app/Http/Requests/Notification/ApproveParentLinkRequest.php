<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 親子紐付け承認リクエスト
 * 
 * POST /notifications/{notification_template_id}/approve-parent-link
 */
class ApproveParentLinkRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを実行する権限があるか判定
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // 認証済みユーザーのみ許可
        return $this->user() !== null;
    }

    /**
     * バリデーションルール
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // notification_template_id はルートパラメータから取得
        ];
    }

    /**
     * バリデーションエラーメッセージ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }
}
