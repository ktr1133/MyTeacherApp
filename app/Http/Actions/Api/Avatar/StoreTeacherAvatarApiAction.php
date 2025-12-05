<?php

namespace App\Http\Actions\Api\Avatar;

use App\Http\Requests\Api\Avatar\StoreTeacherAvatarApiRequest;
use App\Http\Responders\Api\Avatar\TeacherAvatarApiResponder;
use App\Services\Avatar\TeacherAvatarServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * API: アバター作成アクション
 * 
 * POST /api/v1/avatar
 * 
 * @package App\Http\Actions\Api\Avatar
 */
class StoreTeacherAvatarApiAction
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
     * アバター作成処理
     *
     * @param StoreTeacherAvatarApiRequest $request
     * @return JsonResponse
     */
    public function __invoke(StoreTeacherAvatarApiRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $data = $request->validated();

            // アバター作成（非同期ジョブでイメージ生成）
            $avatar = $this->avatarService->createAvatar($user, $data);

            return $this->responder->created($avatar);

        } catch (\RuntimeException $e) {
            Log::error('アバター作成エラー', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error($e->getMessage(), 400);

        } catch (\Exception $e) {
            Log::error('アバター作成システムエラー', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('アバターの作成に失敗しました。', 500);
        }
    }
}
