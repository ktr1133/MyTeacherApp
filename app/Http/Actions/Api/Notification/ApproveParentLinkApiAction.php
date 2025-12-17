<?php

namespace App\Http\Actions\Api\Notification;

use App\Jobs\SendPushNotificationJob;
use App\Models\Group;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 親子紐付け承認API
 * 
 * モバイルアプリ用: 子アカウントが保護者からの紐付けリクエストを承認し、
 * parent_user_idとgroup_idを設定します。
 * Phase 6: 親子紐付け機能 - Mobile API実装。
 * 
 * POST /api/notifications/{notification_template_id}/approve-parent-link
 */
class ApproveParentLinkApiAction
{
    /**
     * 親子紐付け承認処理
     *
     * @param Request $request
     * @param int $notificationTemplateId
     * @return JsonResponse
     */
    public function __invoke(Request $request, int $notificationTemplateId): JsonResponse
    {
        $childUser = $request->user();
        
        if (!$childUser) {
            return response()->json([
                'success' => false,
                'message' => '認証が必要です。',
                'errors' => [
                    'auth' => ['認証されていません。']
                ],
            ], 401);
        }
        
        try {
            // 1. 通知テンプレート取得
            $notification = NotificationTemplate::findOrFail($notificationTemplateId);
            
            // 2. 通知種別チェック
            if ($notification->type !== 'parent_link_request') {
                return response()->json([
                    'success' => false,
                    'message' => '無効な通知種別です。',
                    'errors' => [
                        'notification' => ['この通知は親子紐付けリクエストではありません。']
                    ],
                ], 400);
            }
            
            // 3. 通知データからparent_user_id, group_id取得
            $data = $notification->data;
            
            if (!isset($data['parent_user_id']) || !isset($data['group_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => '通知データが不正です。',
                    'errors' => [
                        'notification' => ['通知に必要な情報が不足しています。']
                    ],
                ], 400);
            }
            
            $parentUserId = $data['parent_user_id'];
            $groupId = $data['group_id'];
            
            // 4. 保護者アカウント・グループ存在確認
            $parentUser = User::findOrFail($parentUserId);
            $group = Group::findOrFail($groupId);
            
            // 5. 子アカウントの既存グループチェック
            if ($childUser->group_id !== null) {
                return response()->json([
                    'success' => false,
                    'message' => '既に別のグループに所属しているため、紐付けできません。',
                    'errors' => [
                        'group' => ['既にグループに所属しています。']
                    ],
                ], 400);
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
            
            // データベース更新後、最新の情報を取得
            $childUser->refresh();
            
            Log::info('API: Child approved parent link request', [
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
                    Log::info('API: Push notification job dispatched for parent link approval', [
                        'user_notification_id' => $userNotificationId,
                        'parent_user_id' => $parentUserId,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('API: Failed to dispatch push notification job for parent link approval', [
                    'user_notification_id' => $userNotificationId,
                    'parent_user_id' => $parentUserId,
                    'error' => $e->getMessage(),
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => '紐付けが完了しました。保護者アカウントと連携されました。',
                'data' => [
                    'child_user' => [
                        'id' => $childUser->id,
                        'username' => $childUser->username,
                        'parent_user_id' => $childUser->parent_user_id,
                        'group_id' => $childUser->group_id,
                    ],
                    'parent_user' => [
                        'id' => $parentUser->id,
                        'username' => $parentUser->username,
                        'name' => $parentUser->name,
                    ],
                    'group' => [
                        'id' => $group->id,
                        'name' => $group->name,
                    ],
                ],
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('API: Notification or related data not found', [
                'child_user_id' => $childUser->id,
                'notification_template_id' => $notificationTemplateId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '指定された通知または関連データが見つかりませんでした。',
                'errors' => [
                    'notification' => ['通知が見つかりません。']
                ],
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('API: Failed to approve parent link request', [
                'child_user_id' => $childUser->id,
                'notification_template_id' => $notificationTemplateId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '紐付け処理に失敗しました。しばらくしてから再度お試しください。',
                'errors' => [
                    'server' => ['サーバーエラーが発生しました。']
                ],
            ], 500);
        }
    }
}
