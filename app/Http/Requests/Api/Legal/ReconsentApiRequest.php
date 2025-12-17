<?php

namespace App\Http\Requests\Api\Legal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * 再同意API リクエスト
 * 
 * Phase 6C: 再同意プロセス実装
 */
class ReconsentApiRequest extends FormRequest
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
            'privacy_policy_consent' => ['required', 'boolean'],
            'terms_consent' => ['required', 'boolean'],
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
            'privacy_policy_consent.boolean' => 'プライバシーポリシーへの同意は真偽値で指定してください。',
            'terms_consent.required' => '利用規約への同意が必要です。',
            'terms_consent.boolean' => '利用規約への同意は真偽値で指定してください。',
        ];
    }

    /**
     * バリデーション失敗時の処理
     * 
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return void
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
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
