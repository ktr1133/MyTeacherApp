<?php

namespace App\Http\Requests\Batch;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduledTaskRequest extends FormRequest
{
    /**
     * リクエストが許可されているか判定
     */
    public function authorize(): bool
    {
        return $this->user()->canEditGroup();
    }

    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        return [
            'group_id' => ['required', 'exists:groups,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'requires_image' => ['boolean'],
            'requires_approval' => ['required', 'boolean'],
            'reward' => ['required', 'integer', 'min:0'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'auto_assign' => ['boolean'],
            'schedules' => ['required', 'array', 'min:1'],
            'schedules.*.type' => ['required', 'in:daily,weekly,monthly'],
            'schedules.*.time' => ['required', 'date_format:H:i'],
            'schedules.*.days' => ['required_if:schedules.*.type,weekly', 'array'],
            'schedules.*.days.*' => ['integer', 'between:0,6'],
            'schedules.*.dates' => ['required_if:schedules.*.type,monthly', 'array'],
            'schedules.*.dates.*' => ['integer', 'between:1,31'],
            'due_duration_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'due_duration_hours' => ['nullable', 'integer', 'min:0', 'max:8760'],
            'start_date' => ['required', 'date'],
            'end_date' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    $startDate = $this->input('start_date');
                    if ($startDate && strtotime($value) < strtotime($startDate)) {
                        $fail('終了日は開始日以降の日付を指定してください。');
                    }
                },
            ],
            'skip_holidays' => ['boolean'],
            'move_to_next_business_day' => ['boolean'],
            'delete_incomplete_previous' => ['boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }

    /**
     * バリデーションメッセージ
     */
    public function messages(): array
    {
        return [
            'group_id.required' => 'グループを選択してください。',
            'group_id.exists' => '指定されたグループが存在しません。',
            'title.required' => 'タイトルを入力してください。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'description.max' => '説明は5000文字以内で入力してください。',
            'reward.required' => '報酬を入力してください。',
            'reward.integer' => '報酬は整数で入力してください。',
            'reward.min' => '報酬は0以上で入力してください。',
            'requires_image.boolean' => '画像が必要かどうかは真偽値で指定してください。',
            'requires_approval.required' => '承認の有無を指定してください。',
            'requires_approval.boolean' => '承認が必要かどうかは真偽値で指定してください。',
            'schedules.required' => 'スケジュールを設定してください。',
            'schedules.min' => '少なくとも1つのスケジュールを設定してください。',
            'schedules.*.type.required' => 'スケジュールタイプを選択してください。',
            'schedules.*.type.in' => '有効なスケジュールタイプを選択してください。',
            'schedules.*.time.required' => '実行時刻を指定してください。',
            'schedules.*.time.date_format' => '実行時刻はHH:MM形式で入力してください。',
            'schedules.*.days.required_if' => '曜日を選択してください。',
            'schedules.*.dates.required_if' => '日付を選択してください。',
            'start_date.required' => '開始日を指定してください。',
            'end_date.after' => '終了日は開始日より後の日付を指定してください。',
        ];
    }

    /**
     * バリデーション後の処理
     */
    protected function prepareForValidation(): void
    {
        // チェックボックスの値を正規化
        $this->merge([
            'requires_image' => $this->boolean('requires_image'),
            'requires_approval' => $this->boolean('requires_approval'),
            'auto_assign' => $this->boolean('auto_assign'),
            'skip_holidays' => $this->boolean('skip_holidays'),
            'move_to_next_business_day' => $this->boolean('move_to_next_business_day'),
            'delete_incomplete_previous' => $this->boolean('delete_incomplete_previous'),
        ]);
    }
}