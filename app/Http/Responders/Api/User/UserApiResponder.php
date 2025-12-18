<?php

namespace App\Http\Responders\Api\User;

use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * API: ユーザー情報レスポンダ
 * 
 * ユーザー関連APIのJSONレスポンスを生成
 * 
 * @package App\Http\Responders\Api\User
 */
class UserApiResponder
{
    /**
     * 現在のユーザー情報取得成功レスポンス
     * 
     * モバイルアプリのテーマシステム、設定画面で使用する情報を返却
     * email, bio等の詳細情報も含む（モバイル設定画面で必要）
     *
     * @param User $user
     * @return JsonResponse
     */
    public function currentUser(User $user): JsonResponse
    {
        $data = [
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'theme' => $user->theme ?? 'adult',
            'group_id' => $user->group_id,
            'group_edit_flg' => (bool) $user->group_edit_flg,
        ];

        // グループ情報を含める（master_user_idを追加）
        if ($user->group) {
            $data['group'] = [
                'id' => $user->group->id,
                'name' => $user->group->name,
                'master_user_id' => $user->group->master_user_id,
            ];
        } else {
            $data['group'] = null;
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * 認証エラーレスポンス
     *
     * @return JsonResponse
     */
    public function unauthorized(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'ユーザー認証に失敗しました。',
        ], 401);
    }

    /**
     * エラーレスポンス
     *
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public function error(string $message, int $statusCode = 500): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $statusCode);
    }
}
