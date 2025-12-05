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
                return $this->responder->error('無効なイベントタイプです。', 400);
            }

            // アバター取得
            $avatar = $this->avatarService->getUserAvatar($user);

            if (!$avatar || !$avatar->is_visible) {
                // アバター未作成または非表示の場合は空のレスポンス
                return $this->responder->comment('', null);
            }

            // コメント・画像取得（Userを渡す）
            $result = $this->avatarService->getCommentForEvent($user, $event);

            if (!$result) {
                // コメント未設定の場合は空のレスポンス
                return $this->responder->comment('', null);
            }

            return $this->responder->comment(
                $result['comment'],
                $result['image_url']
            );

        } catch (\Exception $e) {
            Log::error('アバターコメント取得エラー', [
                'user_id' => $request->user()->id,
                'event' => $event,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('コメントの取得に失敗しました。', 500);
        }
    }
}
