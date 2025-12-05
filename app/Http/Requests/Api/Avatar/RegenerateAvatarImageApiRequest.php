<?php

namespace App\Http\Requests\Api\Avatar;

use Illuminate\Foundation\Http\FormRequest;

/**
 * API: アバター画像再生成リクエスト
 */
class RegenerateAvatarImageApiRequest extends FormRequest
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
        // 再生成は既存アバターに対して実行されるため、追加パラメータ不要
        return [];
    }
}
