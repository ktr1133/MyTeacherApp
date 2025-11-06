<?php

namespace App\Repositories\Profile;

use App\Models\Group;
use Illuminate\Database\Eloquent\Collection;

interface GroupRepositoryInterface
{
    /**
     * グループを新規作成する。
     * @param array<string, mixed> $attrs
     * @return Group
     */
    public function create(array $attrs): Group;

    /**
     * グループ情報を更新する。
     * @param array<string, mixed> $attrs
     * @return Group
     */
    public function update(Group $group, array $attrs): Group;

    /**
     * グループ名を変更する。
     * @param string $name
     * @return Group
     */
    public function rename(Group $group, string $name): Group;

    /**
     * グループのメンバーを取得する。
     * @param Group $group
     * @return Collection<int,User>
     */
    public function members(Group $group): Collection;
}