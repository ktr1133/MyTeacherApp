<?php

namespace App\Repositories\Profile;

use App\Models\User;
use App\Repositories\Profile\GroupUserRepositoryInterface;

class GroupUserRepository implements GroupUserRepositoryInterface
{
    /**
     * グループユーザーを新規作成する。
     * @param array<string, mixed> $attrs
     * @return User
     */
    public function create(array $attrs): User
    {
        return User::create($attrs);
    }

    /**
     * グループユーザー情報を更新する。
     * @param User $user
     * @param array<string, mixed> $attrs
     * @return User
     */
    public function update(User $user, array $attrs): User
    {
        $user->fill($attrs);
        $user->save();
        return $user->refresh();
    }
}