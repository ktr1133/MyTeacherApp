<?php

namespace App\Http\Actions\Notification;

use App\Services\Notification\NotificationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 未読通知件数取得アクション（非同期API）
 * 
 * 未読通知件数と最新の通知情報を返す。
 * フロントエンドのポーリングで使用。
 * 
 * @package App\Http\Actions\Notification
 */
class GetUnreadCountAction
{
    /**
     * コンストラクタ
     *
     * @param NotificationServiceInterface $service 通知サービス
     */
    public function __construct(
        private NotificationServiceInterface $service
    ) {}

    /**
     * 未読通知件数と最新通知を取得
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $lastCheckedAt = $request->input('last_checked_at');

        $data = $this->service->getUnreadCountWithNew($userId, $lastCheckedAt);

        return response()->json($data);
    }
}