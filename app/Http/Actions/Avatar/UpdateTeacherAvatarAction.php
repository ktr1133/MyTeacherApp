<?php

namespace App\Http\Actions\Avatar;

use App\Http\Requests\Avatar\StoreTeacherAvatarRequest as Request;
use App\Services\Avatar\TeacherAvatarServiceInterface;

/**
 * 教師アバター更新アクション
 */
class UpdateTeacherAvatarAction
{
    public function __construct(
        private TeacherAvatarServiceInterface $teacherAvatarService
    ) {}

    public function __invoke(Request $request)
    {
        $avatar = $this->teacherAvatarService->getUserAvatar($request->user());
        
        if (!$avatar) {
            return redirect()->route('avatars.create');
        }

        $validated = $request->validated();

        $this->teacherAvatarService->updateAvatar($avatar, $validated);

        return redirect()
            ->route('avatars.edit')
            ->with('success', '教師アバターの設定を更新しました。');
    }
}