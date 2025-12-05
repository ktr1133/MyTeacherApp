<?php

namespace App\Http\Actions\Api\Notification;

use App\Http\Responders\Api\Notification\NotificationApiResponder;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: 通知既読化アクション
 * 
 * PATCH /api/v1/notifications/{id}/read
 * 
 * @package App\Http\Actions\Api\Notification
 */
class MarkNotificationAsReadApiAction
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
     * 通知既読化処理
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();

            // 通知取得
            $notification = UserNotification::where('user_id', $user->id)->find($id);

            if (!$notification) {
                return $this->responder->error('通知が見つかりません。', 404);
            }

            // 既読化
            $notification->markAsRead();
            $notification->refresh();

            return $this->responder->markAsRead($notification);

        } catch (\Exception $e) {
            Log::error('通知既読化エラー', [
                'user_id' => $request->user()->id,
                'notification_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('通知の既読化に失敗しました。', 500);
        }
    }
}
