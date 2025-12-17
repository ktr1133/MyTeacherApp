<?php

namespace App\Http\Requests\Legal;

use Illuminate\Foundation\Http\FormRequest;

/**
 * プライバシーポリシー・利用規約 再同意リクエスト
 * 
 * Phase 6C: 再同意プロセス実装
 */
class ReconsentRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを行う権限があるか判定
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'privacy_policy_consent' => ['required', 'accepted'],
            'terms_consent' => ['required', 'accepted'],
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
            'privacy_policy_consent.required' => 'プライバシーポリシーへの同意が必要です。',
            'privacy_policy_consent.accepted' => 'プライバシーポリシーに同意してください。',
            'terms_consent.required' => '利用規約への同意が必要です。',
            'terms_consent.accepted' => '利用規約に同意してください。',
        ];
    }
}
