<?php

namespace App\Responders\Profile\Group;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class GroupResponder
{
    /**
     * 編集画面を表示
     */
    public function viewEdit($group, $members): View
    {
        return view('profile.group.edit', [
            'group' => $group,
            'groupMembers' => $members,
        ]);
    }

    /**
     * ステータス付きで編集画面へリダイレクト
     */
    public function redirectToEditWithStatus(string $status): RedirectResponse
    {
        return redirect()->route('group.edit')->with('status', $status);
    }
}