<?php

namespace App\Responders\Profile\Group;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class GroupResponder
{
    /**
     * 編集画面を表示
     */
    public function viewEdit($group, $members, bool $hasSubscription = false): View
    {
        return view('profile.group.edit', [
            'group' => $group,
            'groupMembers' => $members,
            'hasSubscription' => $hasSubscription,
        ]);
    }

    /**
     * ステータス付きで編集画面へリダイレクト
     */
    public function redirectToEditWithStatus(array $data): RedirectResponse
    {
        return redirect()
            ->route('group.edit')
            ->with('status', $data['status'])
            ->with('avatar_event', $data['avatar_event'] ?? null);
    }
}