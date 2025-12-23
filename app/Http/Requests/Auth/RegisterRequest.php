<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * リクエストが認可されているかどうかを判定する
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルールを取得する
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()      // 英字必須
                    ->mixedCase()    // 大文字小文字必須
                    ->numbers()      // 数字必須
                    ->symbols()      // 記号必須
                    ->uncompromised(), // 漏洩パスワードチェック
            ],
            // タイムゾーンはデフォルト値があるため、バリデーション不要
            // 同意チェックボックス（法的要件）
            'privacy_policy_consent' => ['required', 'accepted'],
            'terms_consent' => ['required', 'accepted'],
            // Phase 5-2: 13歳未満新規登録時の保護者メール同意
            'birthdate' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'parent_email' => ['nullable', 'email', 'max:255', 'required_if:birthdate,<,' . now()->subYears(13)->format('Y-m-d')],
        ];

        return $rules;
    }

    /**
     * バリデーションエラーメッセージをカスタマイズする
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => 'ユーザー名は必須です。',
            'username.unique' => 'このユーザー名は既に使用されています。',
            'username.max' => 'ユーザー名は255文字以内で入力してください。',
            'email.required' => 'メールアドレスは必須です。',
            'email.email' => '有効なメールアドレスを入力してください。',
            'email.unique' => 'このメールアドレスは既に使用されています。',
            'email.max' => 'メールアドレスは255文字以内で入力してください。',
            'password.required' => 'パスワードは必須です。',
            'password.confirmed' => 'パスワードが一致していません。',
            // 同意チェックボックスのエラーメッセージ
            'privacy_policy_consent.required' => 'プライバシーポリシーへの同意が必要です。',
            'privacy_policy_consent.accepted' => 'プライバシーポリシーへの同意が必要です。',
            'terms_consent.required' => '利用規約への同意が必要です。',
            'terms_consent.accepted' => '利用規約への同意が必要です。',
            // Phase 5-2: 生年月日・保護者メールのエラーメッセージ
            'birthdate.date' => '有効な生年月日を入力してください。',
            'birthdate.before' => '生年月日は今日より前の日付である必要があります。',
            'birthdate.after' => '生年月日は1900年1月1日以降である必要があります。',
            'parent_email.required_if' => '13歳未満の方は保護者のメールアドレスが必要です。',
            'parent_email.email' => '有効な保護者のメールアドレスを入力してください。',
            'parent_email.max' => '保護者のメールアドレスは255文字以内で入力してください。',
        ];
    }
}
