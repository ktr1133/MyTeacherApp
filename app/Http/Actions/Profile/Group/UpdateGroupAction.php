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
            'group_name' => ['required', 'string', 'max:255'],
        ]);

        $this->service->createOrUpdateGroup(Auth::user(), $data['group_name']);

        return $this->responder->redirectToEditWithStatus('group-updated');
    }
}