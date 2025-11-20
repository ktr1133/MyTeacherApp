<?php

namespace App\Http\Actions\Profile\Group;

use App\Exceptions\RedirectException;
use App\Models\User;
use App\Services\Profile\GroupServiceInterface;
use App\Responders\Profile\Group\GroupResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * グループメンバーのテーマ設定を切り替える処理
 */
class ToggleMemberThemeAction
{
    public function __construct(
        private GroupServiceInterface $service,
        private GroupResponder $responder
    ) {}

    public function __invoke(Request $request, User $member)
    {
        // 権限チェック
        $user = Auth::user();
        if (!$user) {
            abort(404);
        }

        if (!$this->service->canChangeThemeOf($user, $member)) {
            throw new RedirectException("編集権限がありません。");
        }

        $data = $request->validate([
            'theme' => ['required', 'boolean'],
        ]);

        $this->service->toggleMemberTheme(Auth::user(), $member, (bool)$data['theme']);

        return $this->responder->redirectToEditWithStatus([
            'status' => 'theme-updated',
            'avatar_event' => config('const.avatar_events.group_edited'),
        ]);
    }
}