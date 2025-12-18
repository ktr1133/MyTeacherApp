<?php

namespace App\Repositories\Profile;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;

interface ProfileUserRepositoryInterface
{
    /**
     * 新規ユーザーを作成する。
     *
     * @param array<string, mixed> $data
     * @return User
     */
    public function create(array $data): User;

    /**
     * IDに基づいてユーザーを取得する。
     *
     * @param int $id
     * @return User|null
     */
    public function findById(int $id): ?User;

    /**
     * ユーザー情報を更新する。
     *
     * @param User $user
     * @param array<string, mixed> $data
     * @return bool
     */
    public function update(User $user, array $data): bool;

    /**
     * ユーザーアカウントを削除する。
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user): bool;

    /**
     * ユーザIDに基づいて、編集権限のないメンバーを取得する。
     *
     * @param int $userId
     * @return Collection
     */
    public function getMembersWithoutEditPermission(int $userId): Collection;

    /**
     * グループIDに基づいて、編集権限のないメンバーを取得する。
     *
     * @param int $groupId
     * @return Collection<int, User>
     */
    public function getGroupMembersByGroupId(int $groupId): Collection;

    /**
     * 指定されたユーザーを親とする子ユーザーを取得する。
     *
     * @param int $parentUserId 親ユーザーのID
     * @return Collection<int, User> 子ユーザーのコレクション
     */
    public function getChildrenByParentUserId(int $parentUserId): Collection;

    /**
     * 複数の子ユーザーのparent_emailを一括更新する。
     *
     * @param Collection<int, User> $children 子ユーザーのコレクション
     * @param string $newEmail 新しいメールアドレス
     * @return int 更新された行数
     */
    public function updateChildrenParentEmail(Collection $children, string $newEmail): int;
}
