<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

/**
 * メンバー別概況レポートPDFダウンロードリクエスト
 */
class DownloadMemberSummaryPdfRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを実行する権限があるか判定
     */
    public function authorize(): bool
    {
        // 認証済みユーザーのみアクセス可能
        if (!auth()->check()) {
            return false;
        }
        
        // 対象ユーザーの存在チェック
        $targetUser = \App\Models\User::find($this->input('user_id'));
        if (!$targetUser) {
            return false;
        }
        
        // 現在のユーザーのグループ取得
        $currentUser = auth()->user();
        $group = $currentUser->group;
        
        if (!$group) {
            return false;
        }
        
        // 同じグループのメンバーのみアクセス可能
        return $targetUser->group_id === $group->id;
    }

    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'year_month' => [
                'required',
                'string',
                'regex:/^\d{4}-\d{2}$/',
            ],
            'comment' => [
                'required',
                'string',
                'max:5000',
            ],
            'chart_image' => [
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * バリデーションエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'ユーザーIDは必須です。',
            'user_id.integer' => 'ユーザーIDは整数である必要があります。',
            'user_id.exists' => '指定されたユーザーが見つかりません。',
            'year_month.required' => '年月は必須です。',
            'year_month.regex' => '年月の形式が正しくありません（YYYY-MM形式で指定してください）。',
            'comment.required' => 'コメントは必須です。',
            'comment.max' => 'コメントは5000文字以内で入力してください。',
        ];
    }

    /**
     * バリデーション後の追加チェック
     */
    protected function passedValidation(): void
    {
        // 年月の妥当性チェック
        $yearMonth = $this->input('year_month');
        try {
            \Carbon\Carbon::createFromFormat('Y-m', $yearMonth);
        } catch (\Exception $e) {
            abort(422, '無効な年月が指定されました。');
        }
    }
}
