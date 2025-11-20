<?php

namespace App\Http\Requests\Token;

use Illuminate\Foundation\Http\FormRequest;

/**
 * トークン購入リクエスト
 */
class PurchaseTokenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'package_id' => 'required|exists:token_packages,id',
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
            'package_id.required' => 'パッケージを選択してください。',
            'package_id.exists' => '選択されたパッケージが存在しません。',
        ];
    }
}