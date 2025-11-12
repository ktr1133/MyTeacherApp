<?php

namespace App\Http\Actions\Profile\Group;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use App\Services\Profile\GroupServiceInterface;
use App\Responders\Profile\Group\GroupResponder;

class AddMemberAction
{
    public function __construct(
        private GroupServiceInterface $service,
        private GroupResponder $responder
    ) {}

    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', Rules\Password::defaults()],
            'group_edit_flg' => ['nullable', 'boolean'],
        ]);

        $this->service->addMember(Auth::user(), $data['username'], $data['password'], (bool)($data['group_edit_flg'] ?? false));

        return $this->responder->redirectToEditWithStatus([
            'status' => 'member-added',
            'avatar_event' => config('const.avatar_events.group_edited'),
        ]);
    }
}