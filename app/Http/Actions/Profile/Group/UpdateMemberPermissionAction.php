<?php

namespace App\Http\Actions\Profile\Group;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Services\Profile\GroupServiceInterface;
use App\Responders\Profile\Group\GroupResponder;

class UpdateMemberPermissionAction
{
    public function __construct(
        private GroupServiceInterface $service,
        private GroupResponder $responder
    ) {}

    public function __invoke(Request $request, User $member)
    {
        $data = $request->validate([
            'group_edit_flg' => ['required', 'boolean'],
        ]);

        $this->service->updateMemberPermission(Auth::user(), $member, (bool)$data['group_edit_flg']);

        return $this->responder->redirectToEditWithStatus('permission-updated');
    }
}