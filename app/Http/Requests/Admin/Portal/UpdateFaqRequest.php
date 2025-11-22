<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Portal;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FAQ更新リクエスト
 */
final class UpdateFaqRequest extends FormRequest
{
    /**
     * リクエストが許可されているか判定
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->is_admin;
    }

    /**
     * バリデーションルール
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'in:MyTeacher,KeepItSimple'],
            'category' => ['required', 'string', 'max:50'],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'display_order' => ['required', 'integer', 'min:0'],
            'is_published' => ['required', 'boolean'],
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
            'app_name.required' => 'アプリ名を選択してください。',
            'app_name.in' => 'アプリ名が不正です。',
            'category.required' => 'カテゴリを入力してください。',
            'category.max' => 'カテゴリは50文字以内で入力してください。',
            'question.required' => '質問を入力してください。',
            'question.max' => '質問は255文字以内で入力してください。',
            'answer.required' => '回答を入力してください。',
            'display_order.required' => '表示順を入力してください。',
            'display_order.integer' => '表示順は整数で入力してください。',
            'display_order.min' => '表示順は0以上で入力してください。',
            'is_published.required' => '公開状態を選択してください。',
            'is_published.boolean' => '公開状態が不正です。',
        ];
    }
}
