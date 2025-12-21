<?php

namespace App\Http\Actions\Avatar;

use App\Services\Avatar\TeacherAvatarServiceInterface;
use App\Responders\Avatar\TeacherAvatarResponder;
use Illuminate\Http\Request;

/**
 * 教師アバター画像再生成アクション
 */
class RegenerateAvatarImageAction
{
    public function __construct(
        private TeacherAvatarServiceInterface $teacherAvatarService,
        private TeacherAvatarResponder $responder
    ) {}

    public function __invoke(Request $request)
    {
        $avatar = $this->teacherAvatarService->getUserAvatar($request->user());
        // シード値を新しく生成して設定
        $avatar->seed =  random_int(1, 2147483647);
        $avatar->save();

        if (!$avatar) {
            return redirect()->route('avatars.create');
        }

        try {
            $this->teacherAvatarService->regenerateImages($avatar);

            return redirect()
                ->route('tasks.index')
                ->with('success', 'アバター画像を再生成しています。1〜2分お待ちください。');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}