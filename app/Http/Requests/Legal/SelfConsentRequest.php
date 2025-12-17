<?php

namespace App\Http\Requests\Legal;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 本人同意リクエスト
 * 
 * 13歳到達時の本人同意フォームのバリデーション
 * Phase 6D: 13歳到達時の本人再同意
 */
class SelfConsentRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストの権限を持っているか判定
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
            'privacy_policy_consent' => 'required|accepted',
            'terms_consent' => 'required|accepted',
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
            'privacy_policy_consent.accepted' => 'プライバシーポリシーへの同意が必要です。',
            'terms_consent.required' => '利用規約への同意が必要です。',
            'terms_consent.accepted' => '利用規約への同意が必要です。',
        ];
    }
}
