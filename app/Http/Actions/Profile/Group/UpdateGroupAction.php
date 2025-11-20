<?php

namespace App\Http\Actions\Profile\Group;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Profile\GroupServiceInterface;
use App\Responders\Profile\Group\GroupResponder;

class UpdateGroupAction
{
    public function __construct(
        private GroupServiceInterface $service,
        private GroupResponder $responder
    ) {}

    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $avatarEvent = $this->service->createOrUpdateGroup(Auth::user(), $data['name']);

        return $this->responder->redirectToEditWithStatus([
            'status' => 'group-updated',
            'avatar_event' => $avatarEvent,
        ]);
    }
}