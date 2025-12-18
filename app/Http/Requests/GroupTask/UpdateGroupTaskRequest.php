<?php

namespace App\Http\Requests\GroupTask;

use Illuminate\Foundation\Http\FormRequest;

/**
 * グループタスク更新リクエスト
 * 
 * グループタスク（同じgroup_task_idのタスク全体）を更新する際のバリデーションルール
 */
class UpdateGroupTaskRequest extends FormRequest
{
    /**
     * リクエストが認証されているか判定
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // 認証済みユーザーのみ許可
        // 権限チェックはActionで実施
        return $this->user() !== null;
    }

    /**
     * バリデーションルール
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'span' => ['required', 'integer', 'in:1,3,6'], // config/const.phpに合わせて修正
            'due_date' => ['nullable', 'string'],
            'priority' => ['nullable', 'integer', 'between:1,5'],
            'reward' => ['nullable', 'integer', 'min:0'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'requires_approval' => ['nullable', 'boolean'],
            'requires_image' => ['nullable', 'boolean'],
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
            'title.required' => 'タスク名は必須です。',
            'title.max' => 'タスク名は255文字以内で入力してください。',
            'span.required' => '期間は必須です。',
            'span.in' => '期間は短期、中期、長期のいずれかを選択してください。',
            'priority.between' => '優先度は1〜5の範囲で指定してください。',
            'reward.min' => '報酬は0以上で指定してください。',
            'tags.*.max' => 'タグは50文字以内で入力してください。',
        ];
    }

    /**
     * バリデーション後のデータ整形
     *
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated();

        // booleanフィールドの正規化
        if (isset($data['requires_approval'])) {
            $data['requires_approval'] = (bool) $data['requires_approval'];
        }

        if (isset($data['requires_image'])) {
            $data['requires_image'] = (bool) $data['requires_image'];
        }

        return $data;
    }
}
