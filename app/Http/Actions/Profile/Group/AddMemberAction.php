<?php

namespace App\Http\Actions\Profile\Group;

use App\Http\Requests\Profile\Group\AddMemberRequest;
use Illuminate\Support\Facades\Auth;
use App\Services\Profile\GroupServiceInterface;
use App\Responders\Profile\Group\GroupResponder;

class AddMemberAction
{
    public function __construct(
        private GroupServiceInterface $service,
        private GroupResponder $responder
    ) {}

    public function __invoke(AddMemberRequest $request)
    {
        $validated = $request->validated();

        $this->service->addMember(
            Auth::user(), 
            $validated['username'], 
            $validated['email'],
            $validated['password'], 
            $validated['name'] ?? null,
            (bool)($validated['group_edit_flg'] ?? false)
        );

        return $this->responder->redirectToEditWithStatus([
            'status' => 'member-added',
            'avatar_event' => config('const.avatar_events.group_edited'),
        ]);
    }
}