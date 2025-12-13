<?php

namespace App\Http\Actions\Api\Avatar;

use App\Http\Responders\Api\Avatar\TeacherAvatarApiResponder;
use App\Services\Avatar\TeacherAvatarServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: イベント向けアバターコメント取得アクション
 * 
 * GET /api/v1/avatar/comment/{event}
 * 
 * @package App\Http\Actions\Api\Avatar
 */
class GetAvatarCommentApiAction
{
    /**
     * コンストラクタ
     *
     * @param TeacherAvatarServiceInterface $avatarService
     * @param TeacherAvatarApiResponder $responder
     */
    public function __construct(
        protected TeacherAvatarServiceInterface $avatarService,
        protected TeacherAvatarApiResponder $responder
    ) {}

    /**
     * イベント向けコメント取得処理
     *
     * @param Request $request
     * @param string $event イベント名
     * @return JsonResponse
     */
    public function __invoke(Request $request, string $event): JsonResponse
    {
        try {
            $user = $request->user();

            // イベント検証
            $validEvents = array_keys(config('const.avatar_events'));
            
            if (!in_array($event, $validEvents)) {
                Log::warning('[GetAvatarCommentApiAction] Invalid event type', [
                    'event' => $event,
                ]);
                return $this->responder->error('無効なイベントタイプです。', 400);
            }

            // アバター取得
            $avatar = $this->avatarService->getUserAvatar($user);

            // アバター未作成、非表示、または画像生成未完了の場合は空のレスポンス
            if (!$avatar || !$avatar->is_visible || $avatar->generation_status !== 'completed') {
                return $this->responder->comment('', null, 'avatar-idle');
            }

            // コメント・画像取得（Userを渡す）
            $result = $this->avatarService->getCommentForEvent($user, $event);

            if (!$result) {
                // コメント未設定の場合は空のレスポンス
                return $this->responder->comment('', null, 'avatar-idle');
            }

            // アニメーション種別を決定（イベントに応じた適切なアニメーション）
            $animation = $this->getAnimationForEvent($event);

            return $this->responder->comment(
                $result['comment'],
                $result['imageUrl'],  // ✅ camelCaseに修正
                $animation
            );

        } catch (\Exception $e) {
            Log::error('🎭 [GetAvatarCommentApiAction] アバターコメント取得エラー', [
                'user_id' => $request->user()->id,
                'event' => $event,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('コメントの取得に失敗しました。', 500);
        }
    }

    /**
     * イベントに応じたアニメーション種別を決定
     * 
     * @param string $event イベント名
     * @return string アニメーション種別
     */
    protected function getAnimationForEvent(string $event): string
    {
        return match($event) {
            'task_completed' => 'avatar-joy',
            'group_task_created', 'task_created' => 'avatar-cheer',
            'login' => 'avatar-wave',
            'logout' => 'avatar-goodbye',
            'token_purchased' => 'avatar-celebrate',
            'task_breakdown', 'task_breakdown_refine' => 'avatar-encourage',
            'task_deleted', 'group_deleted' => 'avatar-worry',
            'performance_personal_viewed', 'performance_group_viewed' => 'avatar-applause',
            'tag_created', 'tag_updated' => 'avatar-nod',
            'notification_created' => 'avatar-question',
            default => 'avatar-idle',
        };
    }
}
