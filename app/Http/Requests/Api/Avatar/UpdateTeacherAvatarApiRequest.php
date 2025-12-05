<?php

namespace App\Http\Requests\Api\Avatar;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * API: 教師アバター更新リクエスト
 */
class UpdateTeacherAvatarApiRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを実行する権限があるかを判定
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Cognitoミドルウェアで認証済み
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
            // 外見設定（すべてoptional）
            'sex' => [
                'sometimes',
                Rule::in(array_keys(config('avatar-options.sex'))),
            ],
            'hair_style' => [
                'nullable',
                Rule::in(array_keys(config('avatar-options.hair_style'))),
            ],
            'hair_color' => [
                'sometimes',
                Rule::in(array_keys(config('avatar-options.hair_color'))),
            ],
            'eye_color' => [
                'sometimes',
                Rule::in(array_keys(config('avatar-options.eye_color'))),
            ],
            'clothing' => [
                'sometimes',
                Rule::in(array_keys(config('avatar-options.clothing'))),
            ],
            'accessory' => [
                'nullable',
                Rule::in(array_keys(config('avatar-options.accessory'))),
            ],
            'body_type' => [
                'sometimes',
                Rule::in(array_keys(config('avatar-options.body_type'))),
            ],

            // 性格設定
            'tone' => [
                'sometimes',
                Rule::in(array_keys(config('avatar-options.tone'))),
            ],
            'enthusiasm' => [
                'sometimes',
                Rule::in(array_keys(config('avatar-options.enthusiasm'))),
            ],
            'formality' => [
                'sometimes',
                Rule::in(array_keys(config('avatar-options.formality'))),
            ],
            'humor' => [
                'sometimes',
                Rule::in(array_keys(config('avatar-options.humor'))),
            ],

            // 描画モデル
            'draw_model_version' => [
                'sometimes',
                'string',
                Rule::in(array_keys(config('services.draw_model_versions'))),
            ],
            'is_transparent' => [
                'sometimes',
                'boolean',
            ],
            'is_chibi' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * バリデーションエラーメッセージをカスタマイズ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // 外見設定
            'sex.in' => '選択された性別は無効です。',
            'hair_style.in' => '選択した髪型は無効です。',
            'hair_color.in' => '選択された髪の色は無効です。',
            'eye_color.in' => '選択された目の色は無効です。',
            'clothing.in' => '選択された服装は無効です。',
            'accessory.in' => '選択されたアクセサリーは無効です。',
            'body_type.in' => '選択された体型は無効です。',

            // 性格設定
            'tone.in' => '選択された口調は無効です。',
            'enthusiasm.in' => '選択された熱意は無効です。',
            'formality.in' => '選択された丁寧さは無効です。',
            'humor.in' => '選択されたユーモアは無効です。',

            // 描画モデル
            'draw_model_version.string' => '描画モデルの形式が無効です。',
            'draw_model_version.in' => '選択された描画モデルは無効です。',
            'is_transparent.boolean' => '透過設定の形式が無効です。',
            'is_chibi.boolean' => 'ちびキャラ設定の形式が無効です。',
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
            'sex' => '性別',
            'hair_style' => '髪型',
            'hair_color' => '髪の色',
            'eye_color' => '目の色',
            'clothing' => '服装',
            'accessory' => 'アクセサリー',
            'body_type' => '体型',
            'tone' => '口調',
            'enthusiasm' => '熱意',
            'formality' => 'フォーマルさ',
            'humor' => 'ユーモア',
            'draw_model_version' => '描画モデル',
            'is_transparent' => '背景透過',
            'is_chibi' => 'ちびキャラ',
        ];
    }
}
