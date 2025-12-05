<?php

namespace App\Http\Actions\Api\Notification;

use App\Http\Responders\Api\Notification\NotificationApiResponder;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: 通知一覧取得アクション
 * 
 * GET /api/v1/notifications
 * 
 * @package App\Http\Actions\Api\Notification
 */
class IndexNotificationApiAction
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
     * 通知一覧取得処理
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // 通知一覧取得（ページネーション）
            $notifications = UserNotification::where('user_id', $user->id)
                ->with('template')
                ->latest()
                ->paginate(20);

            // 未読件数取得
            $unreadCount = UserNotification::where('user_id', $user->id)
                ->unread()
                ->count();

            return $this->responder->index($notifications, $unreadCount);

        } catch (\Exception $e) {
            Log::error('通知一覧取得エラー', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('通知一覧の取得に失敗しました。', 500);
        }
    }
}
