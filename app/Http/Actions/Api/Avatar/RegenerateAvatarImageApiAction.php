<?php

namespace App\Http\Actions\Api\Avatar;

use App\Http\Requests\Api\Avatar\RegenerateAvatarImageApiRequest;
use App\Http\Responders\Api\Avatar\TeacherAvatarApiResponder;
use App\Services\Avatar\TeacherAvatarServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * API: アバター画像再生成アクション
 * 
 * POST /api/v1/avatar/regenerate
 * 
 * @package App\Http\Actions\Api\Avatar
 */
class RegenerateAvatarImageApiAction
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
     * アバター画像再生成処理
     *
     * @param RegenerateAvatarImageApiRequest $request
     * @return JsonResponse
     */
    public function __invoke(RegenerateAvatarImageApiRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            // 既存アバター取得
            $avatar = $this->avatarService->getUserAvatar($user);
            if (!$avatar) {
                return $this->responder->error('アバターが見つかりません。', 404);
            }

            // アバター画像再生成（非同期ジョブ）
            $this->avatarService->regenerateImages($avatar);

            return $this->responder->regenerated($avatar);

        } catch (\RuntimeException $e) {
            Log::error('アバター画像再生成エラー', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error($e->getMessage(), 400);

        } catch (\Exception $e) {
            Log::error('アバター画像再生成システムエラー', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('アバター画像の再生成に失敗しました。', 500);
        }
    }
}
