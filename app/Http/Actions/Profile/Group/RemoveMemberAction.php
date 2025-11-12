<?php

namespace App\Http\Actions\Profile\Group;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Services\Profile\GroupServiceInterface;
use App\Responders\Profile\Group\GroupResponder;

class RemoveMemberAction
{
    public function __construct(
        private GroupServiceInterface $service,
        private GroupResponder $responder
    ) {}

    public function __invoke(User $member)
    {
        $this->service->removeMember(Auth::user(), $member);

        return $this->responder->redirectToEditWithStatus([
            'status' => 'member-removed',
            'avatar_event' => config('const.avatar_events.group_edited'),
        ]);
    }
}