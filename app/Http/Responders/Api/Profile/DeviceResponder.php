<?php

namespace App\Http\Responders\Api\Profile;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * デバイス管理APIレスポンダー
 */
class DeviceResponder
{
    /**
     * デバイス一覧レスポンス
     * 
     * @param Collection $devices
     * @return JsonResponse
     */
    public function devicesList(Collection $devices): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $devices->map(function ($device) {
                return [
                    'id' => $device->id,
                    'device_type' => $device->device_type,
                    'device_name' => $device->device_name,
                    'app_version' => $device->app_version,
                    'is_active' => $device->is_active,
                    'fcm_token' => $device->device_token, // 統合テストとの互換性
                    'last_used_at' => $device->last_used_at?->toIso8601String(),
                    'created_at' => $device->created_at->toIso8601String(),
                    'updated_at' => $device->updated_at->toIso8601String(),
                ];
            })->values()->all(),
        ]);
    }

    /**
     * デバイス削除成功レスポンス
     * 
     * @return JsonResponse
     */
    public function deviceDeleted(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'デバイスを削除しました。',
        ]);
    }
}
