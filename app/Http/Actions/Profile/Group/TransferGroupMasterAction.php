<?php

namespace App\Http\Actions\Profile\Group;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Services\Profile\GroupServiceInterface;
use App\Responders\Profile\Group\GroupResponder;

class TransferGroupMasterAction
{
    public function __construct(
        private GroupServiceInterface $service,
        private GroupResponder $responder
    ) {}

    public function __invoke(User $newMaster)
    {
        $this->service->transferMaster(Auth::user(), $newMaster);

        return $this->responder->redirectToEditWithStatus([
            'status' => 'master-transferred',
            'avatar_event' => config('const.avatar_events.group_edited'),
        ]);
    }
}