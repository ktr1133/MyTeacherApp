<?php

namespace App\Http\Responders\Api\Profile;

use Illuminate\Http\JsonResponse;

/**
 * 通知設定APIレスポンダー
 */
class NotificationSettingsResponder
{
    /**
     * 通知設定取得成功レスポンス
     * 
     * @param array<string, bool> $settings 通知設定
     * @return JsonResponse
     */
    public function success(array $settings): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $settings,
        ], 200);
    }

    /**
     * 通知設定更新成功レスポンス
     * 
     * @param array<string, bool> $settings 更新後の通知設定
     * @return JsonResponse
     */
    public function updated(array $settings): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => '通知設定を更新しました。',
            'data' => $settings,
        ], 200);
    }

    /**
     * 通知設定削除（リセット）成功レスポンス
     * 
     * @param array<string, bool> $settings デフォルト設定
     * @return JsonResponse
     */
    public function reset(array $settings): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => '通知設定をデフォルトに戻しました。',
            'data' => $settings,
        ], 200);
    }
}
