<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

/**
 * タスク登録リクエスト
 * 通常タスクとグループタスクの両方に対応
 */
class StoreTaskRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを実行する権限があるか判定
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        $rules = [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'span'        => ['required', 'integer', 'in:1,2,3'],
            'due_date'    => ['nullable', 'string'],
            'priority'    => ['nullable', 'integer', 'between:1,5'],
            'tags'        => ['nullable', 'array'],
            'tags.*'      => ['string', 'max:50'],
        ];
        
        // グループタスクの場合の追加バリデーション
        if ($this->boolean('is_group_task')) {
            $rules['assigned_user_id']  = ['nullable', 'integer', 'exists:users,id'];
            $rules['reward']            = ['required', 'integer', 'min:0'];
            $rules['requires_approval'] = ['nullable', 'boolean'];
            $rules['requires_image']    = ['nullable', 'boolean'];
        }

        return $rules;
    }

    /**
     * バリデーションエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'title.required'          => 'タスク名は必須です。',
            'title.max'               => 'タスク名は255文字以内で入力してください。',
            'span.required'           => '期間は必須です。',
            'span.in'                 => '期間は短期、中期、長期のいずれかを選択してください。',
            'priority.between'        => '優先度は1〜5の範囲で指定してください。',
            'tags.array'              => 'タグは配列形式で指定してください。',
            'tags.*.string'           => 'タグは文字列で指定してください。',
            'tags.*.max'              => 'タグは50文字以内で入力してください。',
            'assigned_user_id.exists' => '指定されたユーザーが見つかりません。',
            'reward.required'         => '報酬は必須です。',
            'reward.min'              => '報酬は0円以上で指定してください。',
        ];
    }

    /**
     * グループタスクかどうか
     */
    public function isGroupTask(): bool
    {
        return $this->boolean('is_group_task');
    }

    /**
     * 画像必須かどうか
     */
    public function requiresImage(): bool
    {
        return $this->boolean('requires_image');
    }

    /**
     * 承認必須かどうか
     */
    public function requiresApproval(): bool
    {
        return $this->boolean('requires_approval');
    }
}