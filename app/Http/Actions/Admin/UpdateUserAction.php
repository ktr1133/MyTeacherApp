<?php

namespace App\Http\Actions\Admin;

use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\Admin\UserServiceInterface;
use Illuminate\Http\RedirectResponse;

class UpdateUserAction
{
    public function __construct(
        private UserServiceInterface $userService
    ) {}

    /**
     * ユーザー情報を更新
     *
     * @param UpdateUserRequest $request
     * @param User $user
     * @return RedirectResponse
     */
    public function __invoke(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userService->updateUser($user, $request->validated());
        
        return redirect()
            ->route('admin.users.index')
            ->with('success', 'ユーザー情報を更新しました');
    }
}