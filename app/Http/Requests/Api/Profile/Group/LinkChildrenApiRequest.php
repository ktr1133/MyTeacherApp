<?php

namespace App\Http\Requests\Api\Profile\Group;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 一括子アカウント紐づけAPIリクエスト
 * 
 * 親が検索した子アカウントを一括でグループに紐づける
 */
class LinkChildrenApiRequest extends FormRequest
{
    /**
     * リクエストの認証判定
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->group_id !== null;
    }

    /**
     * バリデーションルール
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'child_user_ids' => 'required|array|min:1',
            'child_user_ids.*' => 'required|integer|exists:users,id',
        ];
    }

    /**
     * バリデーションエラーメッセージ
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'child_user_ids.required' => '紐づけする子アカウントを選択してください。',
            'child_user_ids.array' => '不正なデータ形式です。',
            'child_user_ids.min' => '最低1人の子アカウントを選択してください。',
            'child_user_ids.*.integer' => '不正なユーザーIDが含まれています。',
            'child_user_ids.*.exists' => '指定された子アカウントが存在しません。',
        ];
    }
}
