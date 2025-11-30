<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // 管理者のみ許可
        $user = $this->user();
        return $user && $user->is_admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $this->route('user')->id],
            'password' => ['nullable', 'string', 'min:8'],
            'is_admin' => ['nullable', 'boolean'],
            'group_edit_flg' => ['nullable', 'boolean'],
            'free_group_task_limit' => ['nullable', 'integer', 'min:0', 'max:100'],
            'free_trial_days' => ['nullable', 'integer', 'min:0', 'max:90'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'username' => 'ユーザー名',
            'password' => 'パスワード',
            'is_admin' => '管理者権限',
            'group_edit_flg' => 'グループ編集権限',
            'free_group_task_limit' => 'グループタスク無料作成回数',
            'free_trial_days' => '無料トライアル期間',
        ];
    }
}
