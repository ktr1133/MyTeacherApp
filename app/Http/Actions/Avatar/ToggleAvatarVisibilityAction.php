<?php

namespace App\Http\Actions\Avatar;

use App\Services\Avatar\TeacherAvatarServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

/**
 * 教師アバター表示・非表示切替アクション
 */
class ToggleAvatarVisibilityAction
{
    public function __construct(
        private TeacherAvatarServiceInterface $service
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $avatar = $this->service->getUserAvatar($request->user());

        if (!$avatar) {
            return redirect()
                ->route('avatars.edit')
                ->with('error', 'Avatar not found');
        }

        $this->service->toggleVisibility($avatar);

        return redirect()
            ->route('avatars.edit')
            ->with('status', 'avatar-visibility-toggled');
    }
}