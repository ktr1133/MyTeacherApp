<?php

namespace App\Http\Actions\Api\Notification;

use App\Jobs\SendPushNotificationJob;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 親子紐付け拒否API
 * 
 * モバイルアプリ用: 子アカウントが保護者からの紐付けリクエストを拒否します。
 * COPPA法遵守のため、拒否した子アカウントはソフトデリートされます。
 * Phase 6: 親子紐付け機能 - Mobile API実装。
 * 
 * POST /api/notifications/{notification_template_id}/reject-parent-link
 */
class RejectParentLinkApiAction
{
    /**
     * 親子紐付け拒否処理
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
            
            // 3. 通知データからparent_user_id取得
            $data = $notification->data;
            
            if (!isset($data['parent_user_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => '通知データが不正です。',
                    'errors' => [
                        'notification' => ['通知に必要な情報が不足しています。']
                    ],
                ], 400);
            }
            
            $parentUserId = $data['parent_user_id'];
            
            // 4. 保護者アカウント存在確認
            $parentUser = User::findOrFail($parentUserId);
            
            // 5. 通知を既読に（削除前に記録）
            UserNotification::where('user_id', $childUser->id)
                ->where('notification_template_id', $notification->id)
                ->update(['is_read' => true, 'read_at' => now()]);
            
            // 6. 保護者に拒否通知作成 + Push通知送信（トランザクション）
            $userNotificationId = null;
            
            DB::transaction(function () use ($childUser, $parentUserId, &$userNotificationId) {
                // 保護者に拒否通知作成
                $parentNotification = NotificationTemplate::create([
                    'sender_id' => 1, // システム管理者ID
                    'source' => 'system',
                    'type' => 'parent_link_rejected',
                    'priority' => 'important',
                    'title' => 'お子様が紐付けを拒否しました',
                    'message' => "{$childUser->username} さんが親子アカウントの紐付けを拒否しました。\n\nCOPPA法により、13歳未満のお子様のアカウントは保護者の管理が必要です。お子様のアカウントは削除されました。\n\n再度アカウントを作成する場合は、保護者同意フローから登録してください。",
                    'data' => [
                        'child_user_id' => $childUser->id,
                        'child_username' => $childUser->username,
                        'deleted_at' => now()->toISOString(),
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
            
            Log::warning('API: Child rejected parent link request - account will be deleted (COPPA violation)', [
                'child_user_id' => $childUser->id,
                'child_username' => $childUser->username,
                'child_email' => $childUser->email,
                'parent_user_id' => $parentUserId,
                'parent_username' => $parentUser->username,
                'deleted_at' => now()->toISOString(),
            ]);
            
            // 7. 保護者にプッシュ通知送信（トランザクション外）
            try {
                if ($userNotificationId) {
                    SendPushNotificationJob::dispatch($userNotificationId, $parentUserId);
                    Log::info('API: Push notification job dispatched for parent link rejection', [
                        'user_notification_id' => $userNotificationId,
                        'parent_user_id' => $parentUserId,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('API: Failed to dispatch push notification job for parent link rejection', [
                    'user_notification_id' => $userNotificationId,
                    'parent_user_id' => $parentUserId,
                    'error' => $e->getMessage(),
                ]);
            }
            
            // 8. 子アカウント削除（ソフトデリート）
            DB::transaction(function () use ($childUser) {
                $childUser->delete(); // soft delete
            });
            
            Log::info('API: Child account soft deleted after parent link rejection', [
                'child_user_id' => $childUser->id,
                'child_username' => $childUser->username,
            ]);
            
            // 9. モバイルアプリ向け: Sanctumトークン無効化
            // モバイルアプリはこのレスポンスを受け取った後、ローカルストレージのトークンを削除してログイン画面に遷移する
            try {
                $childUser->tokens()->delete(); // 全トークン無効化
                Log::info('API: Sanctum tokens revoked for deleted child account', [
                    'child_user_id' => $childUser->id,
                ]);
            } catch (\Exception $e) {
                Log::error('API: Failed to revoke Sanctum tokens', [
                    'child_user_id' => $childUser->id,
                    'error' => $e->getMessage(),
                ]);
            }
            
            // 10. 削除完了レスポンス
            return response()->json([
                'success' => true,
                'message' => 'アカウントが削除されました。COPPA法により、13歳未満の方は保護者の同意と管理が必要です。',
                'data' => [
                    'deleted' => true,
                    'deleted_at' => now()->toISOString(),
                    'reason' => 'parent_link_rejected',
                    'coppa_compliance' => true,
                ],
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('API: Notification or parent user not found', [
                'child_user_id' => $childUser->id,
                'notification_template_id' => $notificationTemplateId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '指定された通知または保護者アカウントが見つかりませんでした。',
                'errors' => [
                    'notification' => ['通知または保護者アカウントが見つかりません。']
                ],
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('API: Failed to reject parent link request', [
                'child_user_id' => $childUser->id,
                'notification_template_id' => $notificationTemplateId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '拒否処理に失敗しました。しばらくしてから再度お試しください。',
                'errors' => [
                    'server' => ['サーバーエラーが発生しました。']
                ],
            ], 500);
        }
    }
}
