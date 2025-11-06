<?php

namespace App\Repositories\Profile;

use App\Models\User;

interface GroupUserRepositoryInterface
{
    /**
     * グループユーザーを新規作成する。
     * @param array<string, mixed> $attrs
     * @return User
     */
    public function create(array $attrs): User;

    /**
     * グループユーザー情報を更新する。
     * @param User $user
     * @param array<string, mixed> $attrs
     * @return User
     */
    public function update(User $user, array $attrs): User;
}