<?php

namespace App\Http\Actions\Profile\Group;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Profile\GroupServiceInterface;
use App\Responders\Profile\Group\GroupResponder;

class EditGroupAction
{
    public function __construct(
        private GroupServiceInterface $service,
        private GroupResponder $responder
    ) {}

    public function __invoke(Request $request)
    {
        $user = Auth::user();
        [$group, $members] = $this->service->getEditData($user);

        return $this->responder->viewEdit($group, $members);
    }
}