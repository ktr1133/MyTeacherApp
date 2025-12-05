<?php

namespace App\Http\Actions\Api\Notification;

use App\Http\Responders\Api\Notification\NotificationApiResponder;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: 全通知既読化アクション
 * 
 * POST /api/v1/notifications/read-all
 * 
 * @package App\Http\Actions\Api\Notification
 */
class MarkAllNotificationsAsReadApiAction
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
     * 全通知既読化処理
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // 未読通知を既読化
            $count = UserNotification::where('user_id', $user->id)
                ->unread()
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);

            return $this->responder->markAllAsRead($count);

        } catch (\Exception $e) {
            Log::error('全通知既読化エラー', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('全通知の既読化に失敗しました。', 500);
        }
    }
}
