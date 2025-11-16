<?php

namespace App\Repositories\Notification;

use App\Models\Group;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;


/**
 * 通知リポジトリの実装クラス
 * 
 * 通知データの永続化・取得を担当。
 * 
 * @package App\Repositories
 */
class NotificationRepository implements NotificationRepositoryInterface
{
    /**
     * ユーザーの通知一覧を取得
     *
     * @param int $userId ユーザーID
     * @param int $perPage 1ページあたりの件数
     * @return LengthAwarePaginator
     */
    public function getUserNotifications(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return UserNotification::where('user_id', $userId)
            ->with(['template' => function ($query) {
                $query->withTrashed(); // 削除された通知も表示
            }])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * ユーザーの未読通知件数を取得
     *
     * @param int $userId ユーザーID
     * @return int
     */
    public function getUnreadCount(int $userId): int
    {
        return UserNotification::where('user_id', $userId)
            ->unread()
            ->count();
    }

    /**
     * 通知を既読にする
     *
     * @param int $userNotificationId ユーザー通知ID
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function markAsRead(int $userNotificationId): void
    {
        $userNotification = UserNotification::findOrFail($userNotificationId);
        $userNotification->markAsRead();
    }

    /**
     * 全通知を既読にする
     *
     * @param int $userId ユーザーID
     * @return void
     */
    public function markAllAsRead(int $userId): void
    {
        UserNotification::where('user_id', $userId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * 通知テンプレートを作成
     *
     * @param array $data 通知データ
     * @return NotificationTemplate
     */
    public function createTemplate(array $data): NotificationTemplate
    {
        return NotificationTemplate::create($data);
    }

    /**
     * 通知テンプレートを更新
     *
     * @param int $templateId 通知テンプレートID
     * @param array $data 更新データ
     * @param int $updatedBy 編集者のユーザーID
     * @return NotificationTemplate
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function updateTemplate(int $templateId, array $data, int $updatedBy): NotificationTemplate
    {
        $template = NotificationTemplate::findOrFail($templateId);
        $data['updated_by'] = $updatedBy;
        $template->update($data);
        return $template->fresh();
    }

    /**
     * 通知テンプレートを削除（ソフトデリート）
     *
     * @param int $templateId 通知テンプレートID
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deleteTemplate(int $templateId): void
    {
        $template = NotificationTemplate::findOrFail($templateId);
        $template->delete(); // ソフトデリート
    }

    /**
     * 期限切れ通知を物理削除
     * 
     * expire_at から 30 日経過したソフトデリート済み通知を削除。
     *
     * @return int 削除された件数
     */
    public function deleteExpiredNotifications(): int
    {
        $threshold = now()->subDays(30);
        
        // 有効期限切れ + 30日経過した通知を物理削除
        return NotificationTemplate::onlyTrashed()
            ->where(function ($query) use ($threshold) {
                $query->where('expire_at', '<', $threshold)
                      ->orWhere(function ($q) use ($threshold) {
                          $q->whereNull('expire_at')
                            ->where('deleted_at', '<', $threshold);
                      });
            })
            ->forceDelete();
    }

    /**
     * 対象ユーザーに通知を配信
     *
     * @param NotificationTemplate $template 通知テンプレート
     * @return int 配信された通知件数
     */
    public function distributeNotification(NotificationTemplate $template): int
    {
        $userIds = $this->getTargetUserIds($template);

        if (empty($userIds)) {
            return 0;
        }

        $notifications = collect($userIds)->map(function ($userId) use ($template) {
            return [
                'user_id' => $userId,
                'notification_template_id' => $template->id,
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        UserNotification::insert($notifications);

        return count($notifications);
    }

    /**
     * 対象ユーザーIDを取得
     * 
     * target_type に応じて配信対象のユーザーIDリストを返す。
     *
     * @param NotificationTemplate $template 通知テンプレート
     * @return array ユーザーIDの配列
     */
    private function getTargetUserIds(NotificationTemplate $template): array
    {
        return match($template->target_type) {
            'all' => User::pluck('id')->toArray(),
            'users' => json_decode($template->target_ids) ?? [],
            'groups' => $this->getUserIdsFromGroups($template->target_ids ?? []),
            default => [],
        };
    }

    /**
     * グループからユーザーIDを取得
     * 
     * group_user 中間テーブルから該当グループのユーザーを取得。
     *
     * @param array $groupIds グループIDの配列
     * @return array ユーザーIDの配列
     */
    private function getUserIdsFromGroups(array $groupIds): array
    {
        return DB::table('group_user')
            ->whereIn('group_id', $groupIds)
            ->pluck('user_id')
            ->unique()
            ->toArray();
    }

    /**
     * 指定日時以降の新規通知を取得
     *
     * @param int $userId ユーザーID
     * @param string|null $since 取得開始日時（ISO8601形式）
     * @param int $limit 取得件数
     * @return Collection
     */
    public function getNewNotificationsSince(int $userId, ?string $since = null, int $limit = 5): Collection
    {
        $query = UserNotification::where('user_id', $userId)
            ->with(['template' => function ($query) {
                $query->withTrashed(); // 削除済みも取得
            }])
            ->latest();

        if ($since) {
            $query->where('created_at', '>', $since);
        }

        return $query->limit($limit)->get();
    }
}