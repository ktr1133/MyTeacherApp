<?php

namespace App\Http\Requests\Task;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreTaskRequest extends Request
{
    /**
     * バリデーション済みのデータを取得する
     *
     * @return array<string, mixed>
     * @throws ValidationException
     */
    public function validated(): array
    {
        $validator = Validator::make(
            $this->all(),
            $this->rules(),
            $this->messages()
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * バリデーションルールを取得する。
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'title'    => 'required|string|max:255',
            'span'     => 'required|string',
            'deadline' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'nullable|integer|min:1|max:3',
            'tags'     => 'nullable|array',
            'tags.*'   => 'integer|exists:tags,id',
        ];
    }

    /**
     * バリデーションメッセージをカスタマイズする。
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'title.required' => 'タイトルは必須です',
            'span.required'  => '期間は必須です',
            'priority.min'   => '優先度は1以上を指定してください',
            'priority.max'   => '優先度は3以下を指定してください',
        ];
    }

    /**
     * リクエストの認可チェックを行う
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }
}