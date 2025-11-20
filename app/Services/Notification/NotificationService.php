<?php

namespace App\Services\Notification;

use App\Models\NotificationTemplate;
use App\Models\User;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Repositories\Profile\GroupRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * 通知サービスの実装クラス
 * 
 * 通知に関するビジネスロジックを担当。
 * 
 * @package App\Services
 */
class NotificationService implements NotificationServiceInterface
{
    /**
     * コンストラクタ
     *
     * @param NotificationRepositoryInterface $notificationRepository 通知リポジトリ
     * @param GroupRepositoryInterface $groupRepository グループリポジトリ
     */
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
        private GroupRepositoryInterface $groupRepository
    ) {}

    /**
     * ユーザーの通知一覧を取得
     *
     * @param int $userId ユーザーID
     * @param int $perPage 1ページあたりの件数
     * @return LengthAwarePaginator
     */
    public function getUserNotifications(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->notificationRepository->getUserNotifications($userId, $perPage);
    }

    /**
     * 未読通知件数を取得
     *
     * @param int $userId ユーザーID
     * @return int
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->notificationRepository->getUnreadCount($userId);
    }

    /**
     * 通知を既読にする
     *
     * @param int $userNotificationId ユーザー通知ID
     * @return void
     */
    public function markAsRead(int $userNotificationId): void
    {
        $this->notificationRepository->markAsRead($userNotificationId);
    }

    /**
     * 全通知を既読にする
     *
     * @param int $userId ユーザーID
     * @return void
     */
    public function markAllAsRead(int $userId): void
    {
        $this->notificationRepository->markAllAsRead($userId);
    }

    /**
     * 管理者通知を作成・配信
     * 
     * トランザクション内で通知テンプレートを作成し、対象ユーザーに配信。
     *
     * @param array $data 通知データ
     * @param int $senderId 送信者（管理者）のユーザーID
     * @return NotificationTemplate
     */
    public function createAndDistributeNotification(array $data, int $senderId): NotificationTemplate
    {
        return DB::transaction(function () use ($data, $senderId) {
            $data['sender_id'] = $senderId;
            $data['source'] = 'admin';
            
            $template = $this->notificationRepository->createTemplate($data);
            $this->notificationRepository->distributeNotification($template);
            
            return $template;
        });
    }

    /**
     * 管理者通知を更新
     *
     * @param int $templateId 通知テンプレートID
     * @param array $data 更新データ
     * @param int $updatedBy 編集者のユーザーID
     * @return NotificationTemplate
     */
    public function updateNotification(int $templateId, array $data, int $updatedBy): NotificationTemplate
    {
        return $this->notificationRepository->updateTemplate($templateId, $data, $updatedBy);
    }

    /**
     * 管理者通知を削除
     *
     * @param int $templateId 通知テンプレートID
     * @return void
     */
    public function deleteNotification(int $templateId): void
    {
        $this->notificationRepository->deleteTemplate($templateId);
    }

    /**
     * 期限切れ通知を削除
     *
     * @return int 削除された件数
     */
    public function cleanupExpiredNotifications(): int
    {
        return $this->notificationRepository->deleteExpiredNotifications();
    }

    /**
     * 対象ユーザーに通知を配信
     *
     * @param int $senderId 送信者ユーザーID
     * @param int $recipientId 受信者ユーザーID
     * @param string $notificationType 通知タイプ
     * @param string $title 通知タイトル
     * @param string $message 通知メッセージ
     * @param string $priority 通知の優先度（info, normal, important）
     * @return void
     */
    public function sendNotification(int $senderId, int $recipientId, string $notificationType, string $title, string $message, string $priority = 'normal'): void
    {
        $data = [
            'sender_id'   => $senderId,
            'source'      => 'system',
            'type'        => $notificationType,
            'title'       => $title,
            'target_type' => 'users',
            'target_ids'  => json_encode([$recipientId]),
            'message'     => $message,
            'priority'    => $priority,
            'publish_at'  => now(),
            'expire_at'   => Carbon::parse(now())->addDays(30),
            'updated_by'  => $senderId,
        ];
        
        DB::transaction(function () use ($data) {
            // 通知テンプレートを作成
            $template = $this->notificationRepository->createTemplate($data);
            // ユーザに通知を送信
            $this->notificationRepository->distributeNotification($template);
        });
    }

    /**
     * 対象グループに通知を配信
     *
     * @param string $notificationType 通知タイプ
     * @param string $title 通知タイトル
     * @param string $message 通知メッセージ
     * @param string $priority 通知の優先度（info, normal, important）
     * @return void
     */
    public function sendNotificationForGroup(string $notificationType, string $title, string $message, string $priority = 'normal'): void
    {
        $user = Auth::user();
        $group = $user->group;
        $targetIds = $this->groupRepository->members($group)->pluck('id')->toArray();

        $data = [
            'sender_id'   => $user->id,
            'source'      => 'system',
            'type'        => $notificationType,
            'title'       => $title,
            'target_type' => 'groups',
            'target_ids'  => json_encode($targetIds),
            'message'     => $message,
            'priority'    => $priority,
            'publish_at'  => now(),
            'expire_at'   => Carbon::parse(now())->addDays(30),
            'updated_by'  => $user->id,
        ];
        
        DB::transaction(function () use ($data) {
            // 通知テンプレートを作成
            $template = $this->notificationRepository->createTemplate($data);
            // ユーザに通知を送信
            $this->notificationRepository->distributeNotification($template);
        });
    }

    /**
     * 未読件数と新規通知を取得（API用）
     *
     * @param int $userId ユーザーID
     * @param string|null $lastCheckedAt 最後のチェック日時（ISO8601形式）
     * @return array ['unread_count' => int, 'new_notifications' => array, 'timestamp' => string]
     */
    public function getUnreadCountWithNew(int $userId, ?string $lastCheckedAt = null): array
    {
        // 未読件数
        $unreadCount = $this->notificationRepository->getUnreadCount($userId);

        // 最後のチェック以降の新規通知（最大5件）
        $newNotifications = collect();
        if ($lastCheckedAt) {
            $notifications = $this->notificationRepository->getNewNotificationsSince($userId, $lastCheckedAt, 5);
            
            $newNotifications = $notifications->map(function ($notification) {
                $template = $notification->template;
                
                return [
                    'id' => $notification->id,
                    'title' => $template?->title ?? '[削除された通知]',
                    'priority' => $template?->priority ?? 'normal',
                    'sender' => $template?->sender?->username ?? '不明',
                    'created_at' => $notification->created_at->toIso8601String(),
                    'is_deleted' => $template === null || $template->trashed(),
                ];
            });
        }

        return [
            'unread_count' => $unreadCount,
            'new_notifications' => $newNotifications->toArray(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * お知らせ一覧ページの検索処理
     *
     * @param array $validated
     * @return Collection
     */
    public function search(array $validated): Collection
    {
        return $this->notificationRepository->search($validated);
    }

    /**
     * お知らせ検索結果表示ページの検索処理
     *
     * @param array $validated
     * @return LengthAwarePaginator
     */
    public function searchForDisplayResult(array $validated): LengthAwarePaginator
    {
        return $this->notificationRepository->searchForDisplayResult($validated);
    }

    /**
     * コイン購入リクエスト通知を作成（親向け）
     * 
     * 【役割】
     * 子どもがコイン購入をリクエストした際、親に通知を送る。
     * 既存の createTaskAssignmentNotification() と異なり、
     * コイン購入という特定の目的に特化した通知。
     * 
     * @param User $parent 通知を受け取る親
     * @param User $child リクエストした子ども
     * @param \App\Models\TokenPackage $package 購入希望のパッケージ
     * @return NotificationTemplate
     */
    public function createPurchaseRequestNotification(User $parent, User $child, $package): NotificationTemplate
    {
        $isChildTheme = $parent->theme === 'child';
        
        $title = $isChildTheme 
            ? "{$child->username}が コインを買いたいって言ってるよ！"
            : "{$child->username}さんがトークン購入を希望しています";
        
        $message = $isChildTheme
            ? "{$package->name}（{$package->tokens}コイン）を買いたいんだって！"
            : "{$package->name}（{$package->tokens}トークン）の購入リクエストです。";
        
        $templateData = [
            'sender_id' => $child->id,
            'source' => 'system',
            'type' => 'purchase_request',
            'priority' => 'normal',
            'title' => $title,
            'message' => $message,
            'action_url' => route('tasks.pending-approvals'),
            'action_text' => $isChildTheme ? '見る' : '承認画面へ',
            'target_type' => 'users',
            'target_ids' => [$parent->id],
            'publish_at' => now(),
        ];
        
        return $this->notificationRepository->createAndDistributeToUser($templateData, $parent->id);
    }

    /**
     * コイン購入承認通知を作成（子ども向け）
     * 
     * 【役割】
     * 親がコイン購入を承認した際、子どもに通知を送る。
     * 既存の createTaskApprovalNotification() と異なり、
     * コイン購入の承認という特定の目的に特化。
     * 
     * @param User $child 通知を受け取る子ども
     * @param User $parent 承認した親
     * @param \App\Models\TokenPackage $package 承認されたパッケージ
     * @return NotificationTemplate
     */
    public function createPurchaseApprovedNotification(User $child, User $parent, $package): NotificationTemplate
    {
        $isChildTheme = $child->theme === 'child';
        
        $title = $isChildTheme 
            ? "やった！コインを買ってもらえたよ！"
            : "トークンが購入されあなたに付与されました。";
        
        $message = $isChildTheme
            ? "{$parent->username}が{$package->name}を買ってくれたよ！"
            : "{$parent->username}さんが{$package->name}を購入しトークンをあなたに付与しました。";
        
        $templateData = [
            'sender_id' => $parent->id,
            'source' => 'system',
            'type' => 'purchase_approved',
            'priority' => 'normal',
            'title' => $title,
            'message' => $message,
            'action_url' => route('dashboard'),
            'action_text' => $isChildTheme ? '見に行く' : 'トップページへ',
            'target_type' => 'users',
            'target_ids' => [$child->id],
            'publish_at' => now(),
        ];
        
        return $this->notificationRepository->createAndDistributeToUser($templateData, $child->id);
    }

    /**
     * コイン購入却下通知を作成（子ども向け）
     * 
     * 【役割】
     * 親がコイン購入を却下した際、子どもに通知を送る。
     * 却下理由も表示する点が特徴。
     * 
     * @param User $child 通知を受け取る子ども
     * @param User $parent 却下した親
     * @param \App\Models\TokenPackage $package 却下されたパッケージ
     * @param string|null $reason 却下理由
     * @return NotificationTemplate
     */
    public function createPurchaseRejectedNotification(User $child, User $parent, $package, ?string $reason = null): NotificationTemplate
    {
        $isChildTheme = $child->theme === 'child';
        
        $title = $isChildTheme 
            ? "ざんねん...今回はダメだって"
            : "トークン購入が却下されました";
        
        $message = $isChildTheme
            ? "{$parent->username}が「今回はダメ」って。{$package->name}は買えないよ。"
            : "{$parent->username}さんが{$package->name}の購入を却下しました。";
        
        if ($reason) {
            $message .= $isChildTheme 
                ? "\n理由：{$reason}"
                : "\n却下理由：{$reason}";
        }
        
        $templateData = [
            'sender_id' => $parent->id,
            'source' => 'system',
            'type' => 'purchase_rejected',
            'priority' => 'info',
            'title' => $title,
            'message' => $message,
            'action_url' => null,
            'action_text' => null,
            'target_type' => 'users',
            'target_ids' => [$child->id],
            'publish_at' => now(),
        ];
        
        return $this->notificationRepository->createAndDistributeToUser($templateData, $child->id);
    }

    /**
     * コイン購入取り下げ通知を作成（親向け）
     * 
     * 【役割】
     * 子どもがコイン購入リクエストを取り下げた際、親に通知を送る。
     * 新規メソッドとして追加。
     * 
     * @param User $parent 通知を受け取る親
     * @param User $child 取り下げた子ども
     * @param \App\Models\TokenPackage $package 取り下げられたパッケージ
     * @return NotificationTemplate
     */
    public function createPurchaseCanceledNotification(User $parent, User $child, $package): NotificationTemplate
    {
        $isChildTheme = $parent->theme === 'child';
        
        $title = $isChildTheme 
            ? "{$child->username}が やっぱりやめたって"
            : "{$child->username}さんがトークン購入をキャンセルしました";
        
        $message = $isChildTheme
            ? "{$package->name}の「買いたい」をやめたんだって。"
            : "{$package->name}の購入リクエストが取り下げられました。";
        
        $templateData = [
            'sender_id' => $child->id,
            'source' => 'system',
            'type' => 'purchase_canceled',
            'priority' => 'info',
            'title' => $title,
            'message' => $message,
            'action_url' => null,
            'action_text' => null,
            'target_type' => 'users',
            'target_ids' => [$parent->id],
            'publish_at' => now(),
        ];
        
        return $this->notificationRepository->createAndDistributeToUser($templateData, $parent->id);
    }
}