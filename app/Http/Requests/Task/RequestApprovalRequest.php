<?php

namespace App\Http\Requests\Task;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * タスク完了申請リクエスト
 */
class RequestApprovalRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを実行する権限があるか判定
     */
    public function authorize(): bool
    {
        $task = $this->route('task');
        
        // タスクの所有者のみ申請可能
        return $task && $task->user_id === $this->user()->id;
    }

    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        return [
            'images'   => ['nullable', 'array', 'max:3'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif', 'max:10240'], // 10MB
        ];
    }

    /**
     * バリデーションエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'images.array' => '画像は配列形式で指定してください。',
            'images.max' => '画像は最大3枚までアップロードできます。',
            'images.*.image' => 'アップロードされたファイルは画像である必要があります。',
            'images.*.mimes' => '画像はjpeg、png、jpg、gif形式のみ対応しています。',
            'images.*.max' => '画像のサイズは10MB以下にしてください。',
        ];
    }

    /**
     * バリデーション後の追加チェック
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $task = $this->route('task');
            
            if (!$task) {
                return;
            }

            // 画像必須チェック
            if ($task->requires_image) {
                $existingImagesCount = $task->images()->count();
                $newImagesCount = $this->hasFile('images') ? count($this->file('images')) : 0;
                $totalImages = $existingImagesCount + $newImagesCount;
                
                if ($totalImages === 0) {
                    $validator->errors()->add(
                        'images',
                        'このタスクは画像の添付が必須です。'
                    );
                }
            }

            // 画像枚数上限チェック（既存 + 新規 <= 3枚）
            if ($this->hasFile('images')) {
                $existingImagesCount = $task->images()->count();
                $newImagesCount = count($this->file('images'));
                $totalImages = $existingImagesCount + $newImagesCount;
                
                if ($totalImages > 3) {
                    $validator->errors()->add(
                        'images',
                        sprintf(
                            '画像は最大3枚までです。既存の画像が%d枚あるため、新たに%d枚までアップロードできます。',
                            $existingImagesCount,
                            max(0, 3 - $existingImagesCount)
                        )
                    );
                }
            }

            // 既に申請済みでないかチェック
            if ($task->isPendingApproval() || $task->isApproved()) {
                $validator->errors()->add(
                    'task',
                    'このタスクは既に申請済みまたは承認済みです。'
                );
            }
        });
    }
}