<?php

namespace App\Services\Profile;

use App\Models\User;
use App\Models\Group;
use App\Models\FreeTokenSetting;
use App\Services\Profile\GroupServiceInterface;
use App\Services\Token\TokenServiceInterface;
use App\Repositories\Profile\GroupRepositoryInterface;
use App\Repositories\Profile\GroupUserRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class GroupService implements GroupServiceInterface
{
    /**
     * コンストラクタ
     *
     * @param GroupRepositoryInterface $groups
     * @param GroupUserRepositoryInterface $groupUsers
     * @param 
     */
    public function __construct(
        private GroupRepositoryInterface $groups,
        private GroupUserRepositoryInterface $groupUsers,
        private TokenServiceInterface $tokenService,
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
     * @return string
     */
    public function createOrUpdateGroup(User $actor, string $groupName): string
    {
        $group = $actor->group;
        if ($group) {
            if (!$this->canEditGroup($actor)) {
                abort(403, 'グループを編集する権限がありません。');
            }
            $this->groups->rename($group, $groupName);

            return config('const.avatar_events.group_edited');
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

        return config('const.avatar_events.group_created');
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

        return DB::transaction(function () use ($username, $password, $group, $canEdit, &$user): User {
            if (User::where('username', $username)->exists()) {
                abort(422, '指定されたユーザー名は既に存在します。');
            }

            // ユーザー作成＆グループ作成
            return $this->groupUsers->create([
                'username' => $username,
                'password' => Hash::make($password),
                'group_id' => $group->id,
                'group_edit_flg' => $canEdit,
            ]);
        });
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
    public function canEditGroup(User $user): bool
    {
        return $user->group_edit_flg || $this->isGroupMaster($user);
    }

    /**
     * グループメンバーのテーマ設定を切り替える。
     *
     * @param User $actor
     * @param User $member
     * @param bool $theme
     * @return void
     */
    public function toggleMemberTheme(User $actor, User $member, bool $theme): void
    {
        $group = $actor->group;
        if (!$group || !$this->canEditGroup($actor)) {
            abort(403, 'グループメンバーのテーマ設定を変更する権限がありません。');
        }

        // 子ども用テーマ設定
        $mode = $theme ? 'child' : 'adult';
        
        $this->groupUsers->update($member, ['theme' => $mode]);
    }

    /**
     * 指定ユーザーのテーマ設定を変更できるか。
     * @param User $actor
     * @param User $member
     * @return bool
     */
    public function canChangeThemeOf(User $actor, User $member): bool
    {
        return $actor->canChangeThemeOf($member);
    }
}