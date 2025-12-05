<?php

namespace App\Http\Responders\Api\Notification;

use App\Models\UserNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * API: 通知レスポンダ
 * 
 * 通知関連APIのJSONレスポンスを生成。
 * 
 * @package App\Http\Responders\Api\Notification
 */
class NotificationApiResponder
{
    /**
     * 通知一覧取得成功レスポンス
     *
     * @param LengthAwarePaginator $notifications
     * @param int $unreadCount
     * @return JsonResponse
     */
    public function index(LengthAwarePaginator $notifications, int $unreadCount): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $notifications->map(function ($notification) {
                    return $this->formatNotificationData($notification);
                }),
                'unread_count' => $unreadCount,
                'pagination' => [
                    'total' => $notifications->total(),
                    'per_page' => $notifications->perPage(),
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'from' => $notifications->firstItem(),
                    'to' => $notifications->lastItem(),
                ],
            ],
        ], 200);
    }

    /**
     * 通知詳細取得成功レスポンス
     *
     * @param UserNotification $notification
     * @return JsonResponse
     */
    public function show(UserNotification $notification): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'notification' => $this->formatNotificationData($notification),
            ],
        ], 200);
    }

    /**
     * 通知既読化成功レスポンス
     *
     * @param UserNotification $notification
     * @return JsonResponse
     */
    public function markAsRead(UserNotification $notification): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => '通知を既読にしました。',
            'data' => [
                'notification' => $this->formatNotificationData($notification),
            ],
        ], 200);
    }

    /**
     * 全通知既読化成功レスポンス
     *
     * @param int $count
     * @return JsonResponse
     */
    public function markAllAsRead(int $count): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => "すべての通知を既読にしました（{$count}件）。",
            'data' => [
                'marked_count' => $count,
            ],
        ], 200);
    }

    /**
     * 未読通知件数取得成功レスポンス
     *
     * @param int $count
     * @return JsonResponse
     */
    public function unreadCount(int $count): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count,
            ],
        ], 200);
    }

    /**
     * 通知検索成功レスポンス
     *
     * @param LengthAwarePaginator $notifications
     * @param array $searchTerms
     * @param string $operator
     * @return JsonResponse
     */
    public function searchResults(
        LengthAwarePaginator $notifications,
        array $searchTerms,
        string $operator
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $notifications->map(function ($notification) {
                    return $this->formatNotificationData($notification);
                }),
                'search_params' => [
                    'terms' => $searchTerms,
                    'operator' => $operator,
                ],
                'pagination' => [
                    'total' => $notifications->total(),
                    'per_page' => $notifications->perPage(),
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'from' => $notifications->firstItem(),
                    'to' => $notifications->lastItem(),
                ],
            ],
        ], 200);
    }

    /**
     * エラーレスポンス
     *
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public function error(string $message, int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $statusCode);
    }

    /**
     * 通知データをフォーマット
     *
     * @param UserNotification $notification
     * @return array
     */
    private function formatNotificationData(UserNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'user_id' => $notification->user_id,
            'notification_template_id' => $notification->notification_template_id,
            'is_read' => $notification->is_read,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at->toIso8601String(),
            'updated_at' => $notification->updated_at->toIso8601String(),
            'template' => $notification->template ? [
                'id' => $notification->template->id,
                'title' => $notification->template->title,
                'content' => $notification->template->content,
                'priority' => $notification->template->priority,
                'category' => $notification->template->category,
            ] : null,
        ];
    }
}
