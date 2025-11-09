<?php

namespace App\Http\Actions\Admin;

use App\Models\User;
use App\Responders\Admin\User\EditUserResponder;
use App\Services\Admin\UserServiceInterface;
use Illuminate\Contracts\View\View;

class EditUserAction
{
    public function __construct(
        private UserServiceInterface $userService,
        private EditUserResponder $responder
    ) {}

    /**
     * ユーザー編集画面を表示
     *
     * @param User $user
     * @return View
     */
    public function __invoke(User $user): View
    {
        $data = $this->userService->getUserEditData($user->id);
        
        return $this->responder->respond($data);
    }
}