<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

/**
 * お問い合わせ作成リクエスト
 */
class StoreContactRequest extends FormRequest
{
    /**
     * リクエストが承認されるかどうかを判定
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * リクエストのバリデーションルール
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:1000'],
            'app_name' => ['required', 'string', 'in:myteacher,app2,app3,general'],
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
            'name.required' => '氏名を入力してください。',
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => '正しいメールアドレス形式で入力してください。',
            'subject.required' => '件名を入力してください。',
            'message.required' => 'お問い合わせ内容を入力してください。',
            'message.min' => 'お問い合わせ内容は10文字以上で入力してください。',
            'message.max' => 'お問い合わせ内容は1000文字以内で入力してください。',
            'app_name.required' => 'お問い合わせ対象アプリを選択してください。',
        ];
    }
}
