<?php

namespace App\Http\Actions\Avatar;

use App\Responders\Avatar\TeacherAvatarResponder;
use App\Services\Avatar\TeacherAvatarServiceInterface;
use Illuminate\Http\Request;

/**
 * 教師アバター作成画面表示アクション
 */
class CreateTeacherAvatarAction
{
    public function __construct(
        private TeacherAvatarServiceInterface $service,
        private TeacherAvatarResponder $responder
    ) {}

    public function __invoke(Request $request)
    {
        $user = $request->user();
        
        // 既にアバターが存在する場合は編集画面へ
        $avatar = $this->service->getUserAvatar($user);
        if ($avatar) {
            return redirect()->route('avatars.edit');
        }

        // テーマに応じたビューを返す（ミドルウェアでセット済み）
        return $this->responder->create();
    }
}