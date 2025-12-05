<?php

namespace App\Http\Actions\Api\Profile;

use App\Services\Timezone\TimezoneServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: タイムゾーン設定取得アクション
 * 
 * 現在のタイムゾーン設定と選択可能なタイムゾーン一覧を取得
 * Cognito認証を前提（middleware: cognito）
 */
class ShowTimezoneSettingApiAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected TimezoneServiceInterface $timezoneService
    ) {}

    /**
     * タイムゾーン設定を取得
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'ユーザー認証に失敗しました。',
                ], 401);
            }

            $currentTimezone = $user->timezone ?? 'Asia/Tokyo';

            return response()->json([
                'success' => true,
                'data' => [
                    'current_timezone' => $currentTimezone,
                    'current_timezone_name' => $this->timezoneService->getTimezoneName($currentTimezone),
                    'timezones_grouped' => $this->timezoneService->getTimezonesGroupedByRegion(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('タイムゾーン設定取得エラー', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'タイムゾーン設定の取得に失敗しました。',
            ], 500);
        }
    }
}
