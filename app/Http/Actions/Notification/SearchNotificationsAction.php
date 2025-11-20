<?php

namespace App\Http\Actions\Notification;

use App\Http\Requests\Notification\SearchNotificationRequest as Request;
use App\Models\NotificationTemplate;
use App\Services\Notification\NotificationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;


/**
 * 通知検索アクション
 * 
 * 通知を検索するためのAPI。
 */
class SearchNotificationsAction
{
    /**
     * コンストラクタ
     *
     * @param NotificationServiceInterface $notificationService
     */
    public function __construct(
        private NotificationServiceInterface $notificationService,
    ) {}
    /**
     * 通知を検索
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'Unauthorized');
        }

        $validated = $request->validated();

        $userNotifications = $this->notificationService->search($validated);

        return response()->json([
            'notifications' => $userNotifications->map(function ($userNotification) {
                $template = $userNotification->template;
                
                // テンプレートが削除されている場合
                if (!$template || $template->trashed()) {
                    return [
                        'id'         => $userNotification->id,
                        'title'      => '[削除された通知]',
                        'priority'   => 'info',
                        'source'     => 'system',
                        'source_label' => 'システム',
                        'publish_at' => $userNotification->created_at->format('Y年m月d日'),
                        'sender'     => '不明',
                        'is_read'    => $userNotification->is_read,
                    ];
                }

                return [
                    'id'           => $userNotification->id,
                    'title'        => $template->title,
                    'priority'     => $template->priority,
                    'source'       => $template->source,
                    'source_label' => $template->source === 'admin' ? '公式' : 'システム',
                    'publish_at'   => $template->publish_at?->format('Y年m月d日'),
                    'sender'       => $template->sender->username,
                    'is_read'      => $userNotification->is_read,
                ];
            }),
        ]);
    }

    /**
     * 配信対象タイプのラベルを取得
     *
     * @param string $targetType
     * @return string
     */
    private function getTargetTypeLabel(string $targetType): string
    {
        return match($targetType) {
            'all'    => '全ユーザー',
            'users'  => '特定ユーザー',
            'groups' => '特定グループ',
            default  => '不明',
        };
    }
}