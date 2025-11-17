<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 通知検索リクエスト
 */
class SearchNotificationRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを実行する権限があるかを判定
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // 認証済みユーザーのみ許可
        return $this->user() !== null;
    }

    /**
     * バリデーションルールを取得
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'operator' => 'required|in:or,and',
            'terms' => 'required|array',
            'terms.*' => 'required|string|max:255',
        ];
    }

    /**
     * バリデーション前にデータを準備
     * 
     * user_id を自動的に追加
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => $this->user()->id,
        ]);
    }

    /**
     * バリデーション済みデータを取得
     * 
     * user_id を含めて返す
     *
     * @param array|int|string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function validated($key = null, $default = null): mixed
    {
        logger()->info('validated called', ['key' => $key, 'default' => $default]);
        $validated = parent::validated($key, $default);

        // user_id が含まれていない場合は追加
        if (is_array($validated) && !isset($validated['user_id'])) {
            $validated['user_id'] = $this->user()->id;
        }

        return $validated;
    }

    /**
     * バリデーションエラーメッセージをカスタマイズ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'operator.required' => '検索演算子を指定してください。',
            'operator.in' => '検索演算子は "or" または "and" である必要があります。',
            'terms.required' => '検索キーワードを入力してください。',
            'terms.array' => '検索キーワードの形式が不正です。',
            'terms.*.required' => '検索キーワードは空にできません。',
            'terms.*.max' => '検索キーワードは255文字以内で入力してください。',
        ];
    }

    /**
     * バリデーション属性名をカスタマイズ
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'operator' => '検索演算子',
            'terms' => '検索キーワード',
            'terms.*' => '検索キーワード',
        ];
    }
}