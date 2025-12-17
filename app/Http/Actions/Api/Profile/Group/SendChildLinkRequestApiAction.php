<?php

namespace App\Http\Actions\Api\Profile\Group;

use App\Http\Requests\Api\Profile\Group\SendChildLinkRequestApiRequest;
use App\Models\User;
use App\Models\NotificationTemplate;
use App\Models\UserNotification;
use App\Jobs\SendPushNotificationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * 紐付けリクエスト送信API
 * 
 * モバイルアプリ用: 保護者から子アカウントへ親子紐付けリクエストを送信します。
 * Phase 6: 親子紐付け機能 - Mobile API実装。
 */
class SendChildLinkRequestApiAction
{
    /**
     * 紐付けリクエストを送信
     * 
     * @param SendChildLinkRequestApiRequest $request HTTPリクエスト
     * @return JsonResponse JSONレスポンス
     */
    public function __invoke(SendChildLinkRequestApiRequest $request): JsonResponse
    {
        try {
            $parentUser = $request->user();
            $childUserId = $request->input('child_user_id');

            // 子アカウント取得
            $childUser = User::findOrFail($childUserId);

            // 既存グループ所属チェック
            if ($childUser->group_id !== null) {
                Log::warning('API: Child already belongs to a group - cannot send link request', [
                    'parent_user_id' => $parentUser->id,
                    'child_user_id' => $childUser->id,
                    'child_group_id' => $childUser->group_id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'お子様は既に別のグループに所属しているため、紐付けリクエストを送信できません。',
                    'errors' => [
                        'child_user_id' => ['お子様は既に別のグループに所属しています。']
                    ],
                ], 400);
            }

            // 保護者のグループ存在確認
            if (!$parentUser->group_id) {
                Log::error('API: Parent does not belong to any group', [
                    'parent_user_id' => $parentUser->id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'グループに所属していないため、紐付けリクエストを送信できません。先にグループを作成してください。',
                    'errors' => [
                        'group_id' => ['グループに所属していません。']
                    ],
                ], 400);
            }

            $userNotificationId = null;

            // トランザクション開始（通知作成）
            DB::transaction(function () use ($parentUser, $childUser, &$userNotificationId) {
                // 通知テンプレート作成
                $notificationTemplate = NotificationTemplate::create([
                    'sender_id' => $parentUser->id,
                    'source' => 'system',
                    'type' => 'parent_link_request',
                    'priority' => 'important',
                    'title' => '保護者アカウントとの紐付けリクエスト',
                    'message' => sprintf(
                        '%s さんから親子アカウントの紐付けリクエストが届いています。' . "\n\n" .
                        'グループ名: %s' . "\n\n" .
                        '承認すると、%s さんがあなたのタスクを管理できるようになります。',
                        $parentUser->name ?? $parentUser->username,
                        $parentUser->group->name,
                        $parentUser->name ?? $parentUser->username
                    ),
                    'data' => [
                        'parent_user_id' => $parentUser->id,
                        'parent_name' => $parentUser->name ?? $parentUser->username,
                        'group_id' => $parentUser->group_id,
                        'group_name' => $parentUser->group->name,
                    ],
                    'target_type' => 'users',
                    'target_ids' => [$childUser->id],
                    'publish_at' => now(),
                    'expire_at' => null, // 期限なし
                ]);

                // ユーザー通知レコード作成
                $userNotification = UserNotification::create([
                    'user_id' => $childUser->id,
                    'notification_template_id' => $notificationTemplate->id,
                    'is_read' => false,
                ]);

                // ユーザー通知IDを保存（トランザクション外で使用）
                $userNotificationId = $userNotification->id;
            });

            // モバイルプッシュ通知送信（非同期ジョブ - トランザクション外で実行）
            try {
                SendPushNotificationJob::dispatch($userNotificationId, $childUser->id);
            } catch (\Exception $e) {
                Log::error('API: Failed to dispatch push notification job for parent link request', [
                    'parent_user_id' => $parentUser->id,
                    'child_user_id' => $childUser->id,
                    'user_notification_id' => $userNotificationId,
                    'error' => $e->getMessage(),
                ]);
                // ジョブディスパッチ失敗してもリクエスト自体は成功とする
            }

            Log::info('API: Parent link request sent', [
                'parent_user_id' => $parentUser->id,
                'child_user_id' => $childUser->id,
                'user_notification_id' => $userNotificationId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'お子様に紐付けリクエストを送信しました。',
                'data' => [
                    'notification_id' => $userNotificationId,
                    'child_user' => [
                        'id' => $childUser->id,
                        'username' => $childUser->username,
                        'name' => $childUser->name,
                    ],
                ],
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('API: Child user not found', [
                'child_user_id' => $request->input('child_user_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '指定された子アカウントが見つかりませんでした。',
                'errors' => [
                    'child_user_id' => ['子アカウントが見つかりません。']
                ],
            ], 404);

        } catch (\Exception $e) {
            Log::error('API: Failed to send parent link request', [
                'parent_user_id' => $request->user()->id ?? null,
                'child_user_id' => $request->input('child_user_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '紐付けリクエストの送信中にエラーが発生しました。',
                'errors' => [
                    'server' => ['サーバーエラーが発生しました。もう一度お試しください。']
                ],
            ], 500);
        }
    }
}
