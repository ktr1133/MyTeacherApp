<?php

namespace App\Http\Actions\Avatar;

use App\Services\Avatar\TeacherAvatarServiceInterface;
use Illuminate\Http\Request;

/**
 * 教師アバター更新アクション
 */
class UpdateTeacherAvatarAction
{
    public function __construct(
        private TeacherAvatarServiceInterface $service
    ) {}

    public function __invoke(Request $request)
    {
        $avatar = $this->service->getUserAvatar($request->user());
        
        if (!$avatar) {
            return redirect()->route('avatars.create');
        }

        $validated = $request->validate([
            'sex' => 'required|in:male,female,other',
            'hair_color' => 'required|in:black,brown,blonde,silver,red',
            'eye_color' => 'required|in:brown,blue,green,gray,purple',
            'clothing' => 'required|in:suit,casual,kimono,robe,dress',
            'accessory' => 'nullable|in:glasses,hat,tie',
            'body_type' => 'required|in:average,slim,sturdy',
            'tone' => 'required|in:gentle,strict,friendly,intellectual',
            'enthusiasm' => 'required|in:high,normal,modest',
            'formality' => 'required|in:polite,casual,formal',
            'humor' => 'required|in:high,normal,low',
        ]);

        $this->service->updateAvatar($avatar, $validated);

        return redirect()
            ->route('avatars.edit')
            ->with('success', '教師アバターの設定を更新しました。');
    }
}