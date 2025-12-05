<?php

namespace App\Http\Actions\Api\Notification;

use App\Http\Responders\Api\Notification\NotificationApiResponder;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: 未読通知件数取得アクション
 * 
 * GET /api/v1/notifications/unread-count
 * 
 * @package App\Http\Actions\Api\Notification
 */
class GetUnreadCountApiAction
{
    /**
     * コンストラクタ
     *
     * @param NotificationApiResponder $responder
     */
    public function __construct(
        protected NotificationApiResponder $responder
    ) {}

    /**
     * 未読通知件数取得処理
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // 未読件数取得
            $count = UserNotification::where('user_id', $user->id)
                ->unread()
                ->count();

            return $this->responder->unreadCount($count);

        } catch (\Exception $e) {
            Log::error('未読通知件数取得エラー', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('未読通知件数の取得に失敗しました。', 500);
        }
    }
}
