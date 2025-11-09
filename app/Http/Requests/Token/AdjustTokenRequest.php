<?php

namespace App\Http\Requests\Token;

use Illuminate\Foundation\Http\FormRequest;

/**
 * トークン調整リクエスト（管理者用）
 */
class AdjustTokenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // 管理者権限チェックは別途ミドルウェアで実施
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tokenable_id' => 'required|integer',
            'tokenable_type' => 'required|in:App\Models\User,App\Models\Group',
            'amount' => 'required|integer|not_in:0',
            'note' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tokenable_id.required' => '対象IDを指定してください。',
            'tokenable_type.required' => '対象タイプを指定してください。',
            'tokenable_type.in' => '対象タイプが不正です。',
            'amount.required' => '調整量を指定してください。',
            'amount.not_in' => '調整量は0以外を指定してください。',
            'note.max' => 'メモは500文字以内で入力してください。',
        ];
    }
}