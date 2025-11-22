<?php

namespace App\Http\Requests\Avatar;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 教師アバター作成リクエスト
 */
class StoreTeacherAvatarRequest extends FormRequest
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
            // 外見設定
            'sex' => [
                'required',
                Rule::in(array_keys(config('services.sex'))),
            ],
            'hair_style' => [
                'nullable',
                Rule::in(array_keys(config('services.hair_style'))),
            ],
            'hair_color' => [
                'required',
                Rule::in(array_keys(config('services.hair_color'))),
            ],
            'eye_color' => [
                'required',
                Rule::in(array_keys(config('services.eye_color'))),
            ],
            'clothing' => [
                'required',
                Rule::in(array_keys(config('services.clothing'))),
            ],
            'accessory' => [
                'nullable',
                Rule::in(array_keys(config('services.accessory'))),
            ],
            'body_type' => [
                'required',
                Rule::in(array_keys(config('services.body_type'))),
            ],

            // 性格設定
            'tone' => [
                'required',
                Rule::in(array_keys(config('services.tone'))),
            ],
            'enthusiasm' => [
                'required',
                Rule::in(array_keys(config('services.enthusiasm'))),
            ],
            'formality' => [
                'required',
                Rule::in(array_keys(config('services.formality'))),
            ],
            'humor' => [
                'required',
                Rule::in(array_keys(config('services.humor'))),
            ],

            // 描画モデル
            'draw_model_version' => [
                'required',
                'string',
                Rule::in(array_keys(config('services.draw_model_versions'))),
            ],
            'is_transparent' => [
                'sometimes',
                'string',
            ],
            'is_chibi' => [
                'sometimes',
                'string',
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
            'sex.required' => '性別を選択してください。',
            'sex.in' => '選択された性別は無効です。',
            'hair_style.in' => '選択した髪型は無効です。',
            'hair_color.required' => '髪の色を選択してください。',
            'hair_color.in' => '選択された髪の色は無効です。',
            'eye_color.required' => '目の色を選択してください。',
            'eye_color.in' => '選択された目の色は無効です。',
            'clothing.required' => '服装を選択してください。',
            'clothing.in' => '選択された服装は無効です。',
            'accessory.in' => '選択されたアクセサリーは無効です。',
            'body_type.required' => '体型を選択してください。',
            'body_type.in' => '選択された体型は無効です。',

            // 性格設定
            'tone.required' => '口調を選択してください。',
            'tone.in' => '選択された口調は無効です。',
            'enthusiasm.required' => '熱意を選択してください。',
            'enthusiasm.in' => '選択された熱意は無効です。',
            'formality.required' => '丁寧さを選択してください。',
            'formality.in' => '選択された丁寧さは無効です。',
            'humor.required' => 'ユーモアを選択してください。',
            'humor.in' => '選択されたユーモアは無効です。',

            // 描画モデル
            'draw_model_version.required' => '描画モデルを選択してください。',
            'draw_model_version.string' => '描画モデルの形式が無効です。',
            'draw_model_version.in' => '選択された描画モデルは無効です。',
            'is_transparent.string' => '透過設定の形式が無効です。',
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
            'formality' => '丁寧さ',
            'humor' => 'ユーモア',
            'draw_model_version' => '描画モデル',
            'is_transparent' => '透過設定',
        ];
    }

    /**
     * バリデーション済みデータを取得（追加の整形処理）
     *
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // accessoryがnullの場合は'nothing'に変換
        if (!isset($validated['accessory']) || $validated['accessory'] === null) {
            $validated['accessory'] = 'nothing';
        }
        // is_transparentの設定
        $validated['is_transparent'] = isset($validated['is_transparent']) ? true : false;
        // is_chibiの設定
        $validated['is_chibi'] = isset($validated['is_chibi']) ? true : false;

        return $validated;
    }
}