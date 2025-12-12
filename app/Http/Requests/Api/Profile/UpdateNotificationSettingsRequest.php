<?php

namespace App\Http\Requests\Api\Profile;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 通知設定更新リクエスト
 * 
 * @property-read bool|null $push_enabled
 * @property-read bool|null $push_task_enabled
 * @property-read bool|null $push_group_enabled
 * @property-read bool|null $push_token_enabled
 * @property-read bool|null $push_system_enabled
 * @property-read bool|null $push_sound_enabled
 * @property-read bool|null $push_vibration_enabled
 */
class UpdateNotificationSettingsRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを実行する権限があるかを判定
     */
    public function authorize(): bool
    {
        return true; // 認証はmiddlewareで制御
    }

    /**
     * バリデーションルール
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'push_enabled' => ['sometimes', 'boolean'],
            'push_task_enabled' => ['sometimes', 'boolean'],
            'push_group_enabled' => ['sometimes', 'boolean'],
            'push_token_enabled' => ['sometimes', 'boolean'],
            'push_system_enabled' => ['sometimes', 'boolean'],
            'push_sound_enabled' => ['sometimes', 'boolean'],
            'push_vibration_enabled' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * バリデーション後の処理
     * - 許可されていないキーをチェック
     */
    protected function prepareForValidation(): void
    {
        $allowedKeys = [
            'push_enabled',
            'push_task_enabled',
            'push_group_enabled',
            'push_token_enabled',
            'push_system_enabled',
            'push_sound_enabled',
            'push_vibration_enabled',
        ];

        $invalidKeys = array_diff(array_keys($this->all()), $allowedKeys);

        if (!empty($invalidKeys)) {
            // カスタムバリデーションエラーを追加
            foreach ($invalidKeys as $key) {
                $this->merge([$key => null]); // マージして後続のバリデーションで検出させる
            }
        }
    }

    /**
     * カスタムバリデーションルール（不正なキーを検出）
     *
     * @return array<string, array<int, string>>
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $allowedKeys = [
                'push_enabled',
                'push_task_enabled',
                'push_group_enabled',
                'push_token_enabled',
                'push_system_enabled',
                'push_sound_enabled',
                'push_vibration_enabled',
            ];

            $invalidKeys = array_diff(array_keys($this->all()), $allowedKeys);

            foreach ($invalidKeys as $key) {
                $validator->errors()->add($key, "The {$key} field is not allowed.");
            }
        });
    }

    /**
     * バリデーションエラーメッセージのカスタマイズ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            '*.boolean' => ':attribute はtrue/falseで指定してください。',
        ];
    }

    /**
     * 属性名のカスタマイズ
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'push_enabled' => 'プッシュ通知',
            'push_task_enabled' => 'タスク通知',
            'push_group_enabled' => 'グループ通知',
            'push_token_enabled' => 'トークン通知',
            'push_system_enabled' => 'システム通知',
            'push_sound_enabled' => 'サウンド',
            'push_vibration_enabled' => 'バイブレーション',
        ];
    }
}
