<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;

/**
 * サブスクリプションプラン変更リクエスト
 */
class UpdateSubscriptionRequest extends FormRequest
{
    /**
     * リクエストの認可判定
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        // Actionで権限チェック実施
        return true;
    }

    /**
     * バリデーションルール
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'plan' => ['required', 'string', 'in:family,enterprise'],
            'additional_members' => ['nullable', 'integer', 'min:0', 'max:50'],
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
            'plan.required' => 'プランを選択してください。',
            'plan.in' => '無効なプランが選択されました。',
            'additional_members.integer' => '追加メンバー数は整数で指定してください。',
            'additional_members.min' => '追加メンバー数は0以上で指定してください。',
            'additional_members.max' => '追加メンバー数は50以下で指定してください。',
        ];
    }
}
