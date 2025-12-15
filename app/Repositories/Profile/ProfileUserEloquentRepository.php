<?php

namespace App\Repositories\Profile;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;

class ProfileUserEloquentRepository implements ProfileUserRepositoryInterface
{
    /**
     * 新規ユーザーを作成する。
     *
     * @param array<string, mixed> $data
     * @return User
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * IDに基づいてユーザーを取得する。
     *
     * @param int $id
     * @return User|null
     */
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * ユーザー情報を更新する。
     *
     * @param User $user
     * @param array<string, mixed> $data
     * @return bool
     */
    public function update(User $user, array $data): bool
    {
        $user->fill($data);
        return $user->save();
    }

    /**
     * ユーザーアカウントを削除する。
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user): bool
    {
        return $user->delete();
    }

    /**
     * ユーザIDに基づいて、編集権限のないメンバーを取得する。
     *
     * @param int $userId
     * @return Collection
     */
    public function getMembersWithoutEditPermission(int $userId): Collection
    {
        $user = $this->findById($userId);

        if (!$user) {
            abort(404, 'ユーザ情報を取得できませんでした。');
        }

        return User::where('group_id', $user->group_id)
            ->where('group_edit_flg', false)
            ->where('id', '!=', $userId)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupMembersByGroupId(int $groupId): Collection
    {
        return User::query()  // 新しいクエリビルダーを明示的に作成
            ->where('group_id', $groupId)
            ->where('group_edit_flg', false)
            ->orderBy('username')
            ->get();
    }
}
