<?php

namespace App\Http\Actions\Api\Avatar;

use App\Http\Responders\Api\Avatar\TeacherAvatarApiResponder;
use App\Services\Avatar\TeacherAvatarServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: アバター情報取得アクション
 * 
 * GET /api/v1/avatar
 * 
 * @package App\Http\Actions\Api\Avatar
 */
class ShowTeacherAvatarApiAction
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
     * アバター情報取得処理
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
                Log::warning('[ShowTeacherAvatarApiAction] Avatar not found', [
                    'user_id' => $user->id,
                ]);
                return $this->responder->error('アバターが見つかりません。', 404);
            }

            return $this->responder->show($avatar);

        } catch (\Exception $e) {
            Log::error('アバター情報取得エラー', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('アバター情報の取得に失敗しました。', 500);
        }
    }
}
