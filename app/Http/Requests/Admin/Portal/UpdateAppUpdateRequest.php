<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Portal;

use Illuminate\Foundation\Http\FormRequest;

/**
 * アプリ更新履歴更新リクエスト
 */
final class UpdateAppUpdateRequest extends FormRequest
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
            'version' => ['required', 'string', 'max:20'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'release_date' => ['required', 'date'],
            'is_major' => ['required', 'boolean'],
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
            'version.required' => 'バージョンを入力してください。',
            'version.max' => 'バージョンは20文字以内で入力してください。',
            'title.required' => 'タイトルを入力してください。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'description.required' => '説明を入力してください。',
            'release_date.required' => 'リリース日を入力してください。',
            'release_date.date' => 'リリース日は日付形式で入力してください。',
            'is_major.required' => 'メジャーアップデートかどうかを選択してください。',
            'is_major.boolean' => 'メジャーアップデートの値が不正です。',
        ];
    }
}
