<?php

namespace App\Services\Profile;

use App\Models\User;
use App\Models\Group;
use Illuminate\Support\Collection;

interface GroupServiceInterface
{
    /**
     * グループ編集画面のデータを返す。
     *
     * @return array{0: ?Group, 1: Collection<int,User>}
     */
    public function getEditData(User $actor): array;

    /**
     * グループを新規作成または名称更新する。
     */
    public function createOrUpdateGroup(User $actor, string $groupName): Group;

    /**
     * メンバーを追加する。
     */
    public function addMember(User $actor, string $username, string $password, bool $canEdit): User;

    /**
     * メンバーの編集権限を更新する。
     */
    public function updateMemberPermission(User $actor, User $member, bool $canEdit): void;

    /**
     * グループマスターを譲渡する。
     */
    public function transferMaster(User $actor, User $newMaster): void;

    /**
     * メンバーをグループから外す。
     */
    public function removeMember(User $actor, User $member): void;
}