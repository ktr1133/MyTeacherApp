<?php

namespace App\Http\Actions\Api\Notification;

use App\Http\Responders\Api\Notification\NotificationApiResponder;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: 通知詳細取得アクション
 * 
 * GET /api/v1/notifications/{id}
 * 
 * @package App\Http\Actions\Api\Notification
 */
class ShowNotificationApiAction
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
     * 通知詳細取得処理
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
            $notification = UserNotification::where('user_id', $user->id)
                ->with('template')
                ->find($id);

            if (!$notification) {
                return $this->responder->error('通知が見つかりません。', 404);
            }

            // 自動既読（初回表示時）
            if (!$notification->is_read) {
                $notification->markAsRead();
                $notification->refresh();
            }

            return $this->responder->show($notification);

        } catch (\Exception $e) {
            Log::error('通知詳細取得エラー', [
                'user_id' => $request->user()->id,
                'notification_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('通知の取得に失敗しました。', 500);
        }
    }
}
