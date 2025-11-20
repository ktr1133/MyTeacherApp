<?php

namespace App\Http\Actions\Notification;

use App\Http\Responders\Notification\NotificationResponder as Responder;
use App\Models\NotificationTemplate;
use App\Services\Notification\NotificationServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * 通知検索結果表示アクション
 */
class SearchResultsNotificationAction
{
    /**
     * コンストラクタ
     *
     * @param NotificationServiceInterface $notificationService
     * @param Responder $responder
     */
    public function __construct(
        private NotificationServiceInterface $notificationService,
        private Responder $responder
    ) {}

    /**
     * 検索結果を表示
     *
     * @param Request $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'Unauthorized');
        }

        $search = $request->all();
        $search['user_id'] = $user->id;

        $notifications = $this->notificationService->searchForDisplayResult($search);

        return $this->responder->searchResults($notifications, $search['terms'] ?? null, $search['operator'] ?? null);
    }
}