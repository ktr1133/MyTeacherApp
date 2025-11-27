<?php

namespace App\Services\Profile;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface ProfileManagementServiceInterface
{
    /**
     * ユーザープロフィールを更新する。
     *
     * @param User $user
     * @param array<string, mixed> $data
     * @return bool
     */
    public function updateProfile(User $user, array $data): bool;

    /**
     * ユーザーアカウントを削除する。
     *
     * @param User $user
     * @return bool
     */
    public function deleteAccount(User $user): bool;

    /**
     * グループに所属する編集権限のないメンバーを取得する
     *
     * @param int $groupId
     * @return Collection<int, User>
     */
    public function getGroupMembers(int $groupId): Collection;

    /**
     * ユーザーIDからユーザーを取得する
     *
     * @param int $userId
     * @return User|null
     */
    public function findUserById(int $userId): ?User;

    /**
     * 新規ユーザーを作成する
     *
     * @param array<string, mixed> $data
     * @return User
     */
    public function createUser(array $data): User;
}
