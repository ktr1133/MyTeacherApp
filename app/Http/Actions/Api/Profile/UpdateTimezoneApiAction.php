<?php

namespace App\Http\Actions\Api\Profile;

use App\Http\Requests\Api\Profile\UpdateTimezoneApiRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * API: タイムゾーン更新アクション
 * 
 * ユーザーのタイムゾーン設定を更新
 * Cognito認証を前提（middleware: cognito）
 */
class UpdateTimezoneApiAction
{
    /**
     * タイムゾーンを更新
     *
     * @param UpdateTimezoneApiRequest $request
     * @return JsonResponse
     */
    public function __invoke(UpdateTimezoneApiRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'ユーザー認証に失敗しました。',
                ], 401);
            }

            $validated = $request->validated();

            $user->update([
                'timezone' => $validated['timezone'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'タイムゾーンを更新しました。',
                'data' => [
                    'timezone' => $user->timezone,
                    'updated_at' => $user->updated_at->toIso8601String(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('タイムゾーン更新エラー', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'タイムゾーンの更新に失敗しました。',
            ], 500);
        }
    }
}
