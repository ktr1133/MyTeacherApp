<?php

namespace App\Services\Profile;

use App\Models\User;
use App\Models\Group;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use App\Services\Profile\GroupServiceInterface;
use App\Repositories\Profile\GroupRepositoryInterface;
use App\Repositories\Profile\GroupUserRepositoryInterface;

class GroupService implements GroupServiceInterface
{
    /**
     * コンストラクタ
     *
     * @param GroupRepositoryInterface $groups
     * @param GroupUserRepositoryInterface $groupUsers
     */
    public function __construct(
        private GroupRepositoryInterface $groups,
        private GroupUserRepositoryInterface $groupUsers
    ) {}

    /**
     * グループ編集用データ取得
     *
     * @param User $actor
     * @return array [Group|null, Collection<User>]
     */
    public function getEditData(User $actor): array
    {
        $group = $actor->group;
        if ($group && !$this->canEditGroup($actor)) {
            abort(403, 'グループを編集する権限がありません。');
        }
        $members = $group ? $this->groups->members($group) : collect();

        return [$group, $members];
    }

    /**
     * グループ作成または更新
     *
     * @param User $actor
     * @param string $groupName
     * @return Group
     */
    public function createOrUpdateGroup(User $actor, string $groupName): Group
    {
        $group = $actor->group;
        if ($group) {
            if (!$this->canEditGroup($actor)) {
                abort(403, 'グループを編集する権限がありません。');
            }
            return $this->groups->rename($group, $groupName);
        }

        $group = $this->groups->create([
            'name' => $groupName,
            'master_user_id' => $actor->id,
        ]);

        // 参加＆編集権限付与
        $this->groupUsers->update($actor, [
            'group_id' => $group->id,
            'group_edit_flg' => true,
        ]);

        return $group;
    }

    /**
     * グループメンバー追加
     *
     * @param User $actor
     * @param string $username
     * @param string $password
     * @param bool $canEdit
     * @return User
     */
    public function addMember(User $actor, string $username, string $password, bool $canEdit): User
    {
        $group = $actor->group;
        if (!$group || !$this->canEditGroup($actor)) {
            abort(403, 'グループメンバーを追加する権限がありません。');
        }

        return $this->groupUsers->create([
            'username' => $username,
            'password' => Hash::make($password),
            'group_id' => $group->id,
            'group_edit_flg' => $canEdit,
        ]);
    }

    /**
     * グループメンバー権限更新
     *
     * @param User $actor
     * @param User $member
     * @param bool $canEdit
     * @return void
     */
    public function updateMemberPermission(User $actor, User $member, bool $canEdit): void
    {
        $group = $actor->group;
        if (!$group || !$this->canEditGroup($actor)) {
            abort(403, 'グループメンバーの権限を変更する権限がありません。');
        }
        if ($member->group_id !== $group->id) {
            abort(403, '他のグループのメンバーは編集できません。');
        }
        if ($this->isGroupMaster($member)) {
            abort(403, 'グループマスターの権限は変更できません。');
        }

        $this->groupUsers->update($member, ['group_edit_flg' => $canEdit]);
    }

    /**
     * グループマスター譲渡
     *
     * @param User $actor
     * @param User $newMaster
     * @return void
     */
    public function transferMaster(User $actor, User $newMaster): void
    {
        $group = $actor->group;
        if (!$group || !$this->isGroupMaster($actor)) {
            abort(403, 'グループマスターを譲渡する権限がありません。');
        }
        if ($newMaster->group_id !== $group->id) {
            abort(403, '他のグループのメンバーには譲渡できません。');
        }

        $this->groups->update($group, ['master_user_id' => $newMaster->id]);
        $this->groupUsers->update($newMaster, ['group_edit_flg' => true]);
    }

    /**
     * グループメンバー削除
     *
     * @param User $actor
     * @param User $member
     * @return void
     */
    public function removeMember(User $actor, User $member): void
    {
        $group = $actor->group;
        if (!$group || !$this->canEditGroup($actor)) {
            abort(403, 'グループメンバーを削除する権限がありません。');
        }
        if ($member->group_id !== $group->id) {
            abort(403, '他のグループのメンバーは削除できません。');
        }
        if ($this->isGroupMaster($member)) {
            abort(403, 'グループマスターは削除できません。');
        }

        $this->groupUsers->update($member, [
            'group_id' => null,
            'group_edit_flg' => false,
        ]);
    }

    /**
     * グループマスターかどうか確認
     *
     * @param User $user
     * @return bool
     */
    private function isGroupMaster(User $user): bool
    {
        return $user->group && $user->group->master_user_id === $user->id;
    }

    /**
     * グループ編集権限があるか確認
     *
     * @param User $user
     * @return bool
     */
    private function canEditGroup(User $user): bool
    {
        return $user->group_edit_flg || $this->isGroupMaster($user);
    }
}