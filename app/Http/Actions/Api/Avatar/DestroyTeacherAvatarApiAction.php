<?php

namespace App\Http\Actions\Api\Avatar;

use App\Http\Responders\Api\Avatar\TeacherAvatarApiResponder;
use App\Services\Avatar\TeacherAvatarServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: アバター削除アクション
 * 
 * DELETE /api/v1/avatar
 * 
 * @package App\Http\Actions\Api\Avatar
 */
class DestroyTeacherAvatarApiAction
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
     * アバター削除処理
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // アバター取得
            $avatar = $this->avatarService->getUserAvatar($user);

            if (!$avatar) {
                return $this->responder->error('アバターが見つかりません。', 404);
            }

            // アバター削除
            $avatar->delete();

            return $this->responder->deleted();

        } catch (\Exception $e) {
            Log::error('アバター削除エラー', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('アバターの削除に失敗しました。', 500);
        }
    }
}
