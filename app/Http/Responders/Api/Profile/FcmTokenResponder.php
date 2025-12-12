<?php

namespace App\Http\Responders\Api\Profile;

use App\Models\UserDeviceToken;
use Illuminate\Http\JsonResponse;

/**
 * FCMトークンAPIレスポンダー
 */
class FcmTokenResponder
{
    /**
     * FCMトークン登録成功レスポンス
     * 
     * @param UserDeviceToken $token
     * @param bool $isNew 新規登録の場合true、更新の場合false
     * @return JsonResponse
     */
    public function registered(UserDeviceToken $token, bool $isNew = true): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'FCMトークンを登録しました。',
            'data' => $token,
        ], $isNew ? 201 : 200);
    }

    /**
     * FCMトークン削除成功レスポンス
     * 
     * @return JsonResponse
     */
    public function deleted(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'FCMトークンを削除しました。',
        ], 200);
    }

    /**
     * トークン重複エラーレスポンス
     * 
     * @return JsonResponse
     */
    public function conflict(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'このデバイストークンは既に別のユーザーに登録されています。',
        ], 409);
    }
}
