<?php

namespace App\Http\Requests\Api\Token;

use Illuminate\Foundation\Http\FormRequest;

/**
 * トークン購入リクエスト作成用FormRequest
 */
class CreatePurchaseRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // 子どもユーザーのみ作成可能（Actionで再チェック）
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
            'package_id' => 'required|integer|exists:token_packages,id',
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
            'package_id.required' => 'パッケージIDは必須です。',
            'package_id.integer' => 'パッケージIDは整数である必要があります。',
            'package_id.exists' => '指定されたパッケージが存在しません。',
        ];
    }
}
