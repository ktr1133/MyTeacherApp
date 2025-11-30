<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class CreateCheckoutSessionRequest extends FormRequest
{
    /**
     * リクエストが認可されているか判定
     */
    public function authorize(): bool
    {
        return true; // Actionで権限チェック実施
    }

    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        return [
            'plan' => ['required', 'string', 'in:family,enterprise'],
            'additional_members' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    /**
     * バリデーションエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'plan.required' => 'プランを選択してください。',
            'plan.in' => '無効なプランが選択されています。',
            'additional_members.integer' => '追加メンバー数は数値で指定してください。',
            'additional_members.min' => '追加メンバー数は0以上で指定してください。',
            'additional_members.max' => '追加メンバー数は100名までです。',
        ];
    }
}
