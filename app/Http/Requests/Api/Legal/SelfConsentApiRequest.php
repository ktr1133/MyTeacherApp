<?php

namespace App\Http\Requests\Api\Legal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * 本人同意API リクエスト
 * 
 * モバイルアプリから13歳到達時の本人同意を受け取るバリデーション
 * Phase 6D: 13歳到達時の本人再同意
 */
class SelfConsentApiRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストの権限を持っているか判定
     */
    public function authorize(): bool
    {
        // Sanctum認証済みユーザーのみ許可
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
            'privacy_policy_consent' => 'required|boolean',
            'terms_consent' => 'required|boolean',
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
            'privacy_policy_consent.boolean' => 'プライバシーポリシーへの同意はtrue/falseで指定してください。',
            'terms_consent.required' => '利用規約への同意が必要です。',
            'terms_consent.boolean' => '利用規約への同意はtrue/falseで指定してください。',
        ];
    }

    /**
     * バリデーション失敗時の処理（API用JSON返却）
     * 
     * @param Validator $validator
     * @return void
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'error' => 'Validation failed',
                'message' => 'バリデーションエラーが発生しました。',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
