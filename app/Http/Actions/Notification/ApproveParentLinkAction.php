<?php

namespace App\Http\Actions\Notification;

use App\Http\Requests\Notification\ApproveParentLinkRequest;
use App\Jobs\SendPushNotificationJob;
use App\Models\Group;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 親子紐付け承認アクション
 * 
 * 子アカウントが保護者からの紐付けリクエストを承認し、
 * parent_user_idとgroup_idを設定する。
 * 
 * POST /notifications/{notification_template_id}/approve-parent-link
 */
class ApproveParentLinkAction
{
    /**
     * 親子紐付け承認処理
     *
     * @param ApproveParentLinkRequest $request
     * @param int $notificationTemplateId
     * @return RedirectResponse
     */
    public function __invoke(ApproveParentLinkRequest $request, int $notificationTemplateId): RedirectResponse
    {
        $childUser = Auth::user();
        
        try {
            // 1. 通知テンプレート取得
            $notification = NotificationTemplate::findOrFail($notificationTemplateId);
            
            // 2. 通知種別チェック
            if ($notification->type !== 'parent_link_request') {
                return redirect()->back()->withErrors([
                    'error' => '無効な通知種別です。'
                ]);
            }
            
            // 3. 通知データからparent_user_id, group_id取得
            $data = $notification->data;
            
            if (!isset($data['parent_user_id']) || !isset($data['group_id'])) {
                return redirect()->back()->withErrors([
                    'error' => '通知データが不正です。'
                ]);
            }
            
            $parentUserId = $data['parent_user_id'];
            $groupId = $data['group_id'];
            
            // 4. 保護者アカウント・グループ存在確認
            $parentUser = User::findOrFail($parentUserId);
            $group = Group::findOrFail($groupId);
            
            // 5. 子アカウントの既存グループチェック
            if ($childUser->group_id !== null) {
                return redirect()->back()->withErrors([
                    'error' => '既に別のグループに所属しているため、紐付けできません。'
                ]);
            }
            
            // 6. 親子紐付け + グループ参加（トランザクション）
            $userNotificationId = null;
            
            DB::transaction(function () use ($childUser, $parentUserId, $groupId, $notification, $parentUser, &$userNotificationId) {
                // 子アカウントに parent_user_id, group_id 設定
                $childUser->update([
                    'parent_user_id' => $parentUserId,
                    'group_id' => $groupId,
                ]);
                
                // 通知を既読に
                UserNotification::where('user_id', $childUser->id)
                    ->where('notification_template_id', $notification->id)
                    ->update(['is_read' => true, 'read_at' => now()]);
                
                // 保護者に承認通知作成
                $parentNotification = NotificationTemplate::create([
                    'sender_id' => 1, // システム管理者ID
                    'source' => 'system',
                    'type' => 'parent_link_approved',
                    'priority' => 'normal',
                    'title' => 'お子様が紐付けを承認しました',
                    'message' => "{$childUser->username} さんが親子アカウントの紐付けを承認しました。\n\nグループ名: {$parentUser->group->name}\n\nタスク管理機能をご利用いただけます。",
                    'data' => [
                        'child_user_id' => $childUser->id,
                        'child_username' => $childUser->username,
                        'group_id' => $groupId,
                    ],
                    'target_type' => 'users',
                    'target_ids' => [$parentUserId],
                    'publish_at' => now(),
                    'expire_at' => null,
                ]);
                
                $userNotificationRecord = UserNotification::create([
                    'user_id' => $parentUserId,
                    'notification_template_id' => $parentNotification->id,
                    'is_read' => false,
                ]);
                
                $userNotificationId = $userNotificationRecord->id;
            });
            
            Log::info('Child approved parent link request', [
                'child_user_id' => $childUser->id,
                'child_username' => $childUser->username,
                'parent_user_id' => $parentUserId,
                'parent_username' => $parentUser->username,
                'group_id' => $groupId,
            ]);
            
            // 7. 保護者にプッシュ通知送信（トランザクション外）
            try {
                if ($userNotificationId) {
                    SendPushNotificationJob::dispatch($userNotificationId, $parentUserId);
                    Log::info('Push notification job dispatched for parent link approval', [
                        'user_notification_id' => $userNotificationId,
                        'parent_user_id' => $parentUserId,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to dispatch push notification job for parent link approval', [
                    'user_notification_id' => $userNotificationId,
                    'parent_user_id' => $parentUserId,
                    'error' => $e->getMessage(),
                ]);
            }
            
            return redirect()->route('notifications.index')->with('status', '紐付けが完了しました。保護者アカウントと連携されました。');
            
        } catch (\Exception $e) {
            Log::error('Failed to approve parent link request', [
                'child_user_id' => $childUser->id,
                'notification_template_id' => $notificationTemplateId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->back()->withErrors([
                'error' => '紐付け処理に失敗しました。しばらくしてから再度お試しください。'
            ]);
        }
    }
}
