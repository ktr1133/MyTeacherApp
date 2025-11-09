<?php

namespace App\Http\Actions\Admin;

use App\Models\User;
use App\Services\Admin\UserServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DeleteUserAction
{
    public function __construct(
        private UserServiceInterface $userService
    ) {}

    /**
     * ユーザーを削除
     *
     * @param User $user
     * @return RedirectResponse
     */
    public function __invoke(User $user): RedirectResponse
    {
        $result = $this->userService->deleteUser($user, Auth::id());
        
        $flashType = $result['success'] ? 'success' : 'error';
        
        return redirect()
            ->route('admin.users.index')
            ->with($flashType, $result['message']);
    }
}