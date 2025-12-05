<?php

namespace App\Http\Actions\Api\Avatar;

use App\Http\Requests\Api\Avatar\UpdateTeacherAvatarApiRequest;
use App\Http\Responders\Api\Avatar\TeacherAvatarApiResponder;
use App\Services\Avatar\TeacherAvatarServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * API: アバター更新アクション
 * 
 * PUT/PATCH /api/v1/avatar
 * 
 * @package App\Http\Actions\Api\Avatar
 */
class UpdateTeacherAvatarApiAction
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
     * アバター更新処理
     *
     * @param UpdateTeacherAvatarApiRequest $request
     * @return JsonResponse
     */
    public function __invoke(UpdateTeacherAvatarApiRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $data = $request->validated();

            // 既存アバター取得
            $avatar = $this->avatarService->getUserAvatar($user);
            if (!$avatar) {
                return $this->responder->error('アバターが見つかりません。', 404);
            }

            // アバター更新
            $this->avatarService->updateAvatar($avatar, $data);

            // 更新後のアバターを再取得
            $avatar = $this->avatarService->getUserAvatar($user);

            return $this->responder->updated($avatar);

        } catch (\RuntimeException $e) {
            Log::error('アバター更新エラー', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error($e->getMessage(), 400);

        } catch (\Exception $e) {
            Log::error('アバター更新システムエラー', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('アバターの更新に失敗しました。', 500);
        }
    }
}
