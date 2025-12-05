<?php

namespace App\Http\Actions\Api\Avatar;

use App\Http\Responders\Api\Avatar\TeacherAvatarApiResponder;
use App\Services\Avatar\TeacherAvatarServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: アバター表示設定切替アクション
 * 
 * PATCH /api/v1/avatar/visibility
 * 
 * @package App\Http\Actions\Api\Avatar
 */
class ToggleAvatarVisibilityApiAction
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
     * アバター表示設定切替処理
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // 既存アバター取得
            $avatar = $this->avatarService->getUserAvatar($user);
            if (!$avatar) {
                return $this->responder->error('アバターが見つかりません。', 404);
            }

            // アバター表示切替
            $this->avatarService->toggleVisibility($avatar);

            // 更新後のアバターを再取得
            $avatar = $this->avatarService->getUserAvatar($user);

            return $this->responder->visibilityToggled($avatar);

        } catch (\Exception $e) {
            Log::error('アバター表示設定切替エラー', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('アバター表示設定の切替に失敗しました。', 500);
        }
    }
}
