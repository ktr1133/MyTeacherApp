<?php

namespace App\Http\Actions\Auth;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * メールアドレスバリデーションAction
 * 
 * 非同期でメールアドレスの重複チェックを実行
 */
class ValidateEmailAction
{
    /**
     * メールアドレスのバリデーション
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $email = $request->input('email');
        $excludeUserId = $request->input('exclude_user_id'); // プロフィール編集時に自分のメールを除外

        // メールアドレスが空の場合
        if (empty($email)) {
            return response()->json([
                'valid' => false,
                'message' => 'メールアドレスを入力してください。',
            ]);
        }

        // メールアドレス形式チェック
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'valid' => false,
                'message' => '有効なメールアドレスを入力してください。',
            ]);
        }

        // 重複チェック（除外ユーザーID考慮）
        $query = User::where('email', $email);
        
        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }
        
        $exists = $query->exists();

        if ($exists) {
            return response()->json([
                'valid' => false,
                'message' => 'このメールアドレスは既に使用されています。',
            ]);
        }

        return response()->json([
            'valid' => true,
            'message' => '✓ 利用可能なメールアドレスです',
        ]);
    }
}
