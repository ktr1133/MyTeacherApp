<?php

namespace App\Http\Actions\Profile\Group;

use App\Http\Requests\Profile\Group\SendChildLinkRequestRequest;
use App\Responders\Profile\Group\GroupResponder;
use App\Models\User;
use App\Models\NotificationTemplate;
use App\Models\UserNotification;
use App\Jobs\SendPushNotificationJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * 紐付けリクエスト送信アクション
 * 
 * 保護者から子アカウントへ親子紐付けリクエストを送信します。
 * Phase 5-2拡張: 招待トークン失効後のフォールバック機能。
 */
class SendChildLinkRequestAction
{
    /**
     * コンストラクタ
     * 
     * @param GroupResponder $responder レスポンダー
     */
    public function __construct(
        private GroupResponder $responder
    ) {}

    /**
     * 紐付けリクエストを送信
     * 
     * @param SendChildLinkRequestRequest $request HTTPリクエスト
     * @return RedirectResponse リダイレクトレスポンス
     */
    public function __invoke(SendChildLinkRequestRequest $request): RedirectResponse
    {
        $parentUser = $request->user();
        $childUserId = $request->input('child_user_id');

        // 子アカウント取得
        $childUser = User::findOrFail($childUserId);

        // 既存グループ所属チェック
        if ($childUser->group_id !== null) {
            Log::warning('Child already belongs to a group - cannot send link request', [
                'parent_user_id' => $parentUser->id,
                'child_user_id' => $childUser->id,
                'child_group_id' => $childUser->group_id,
            ]);

            return redirect()->back()
                ->withErrors(['child_user_id' => 'お子様は既に別のグループに所属しているため、紐付けリクエストを送信できません。']);
        }

        // 保護者のグループ存在確認
        if (!$parentUser->group_id) {
            Log::error('Parent does not belong to any group', [
                'parent_user_id' => $parentUser->id,
            ]);

            return redirect()->back()
                ->withErrors(['child_user_id' => 'グループに所属していないため、紐付けリクエストを送信できません。先にグループを作成してください。']);
        }

        // トランザクション開始（通知作成 + ジョブディスパッチ）
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
            Log::error('Failed to dispatch push notification job for parent link request', [
                'parent_user_id' => $parentUser->id,
                'child_user_id' => $childUser->id,
                'user_notification_id' => $userNotificationId,
                'error' => $e->getMessage(),
            ]);
            // ジョブディスパッチ失敗してもリクエスト自体は成功とする
        }

        Log::info('Parent link request sent', [
            'parent_user_id' => $parentUser->id,
            'child_user_id' => $childUser->id,
            'user_notification_id' => $userNotificationId,
        ]);

        return redirect()->back()
            ->with('status', 'お子様に紐付けリクエストを送信しました。');
    }
}
