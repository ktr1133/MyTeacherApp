<?php

namespace App\Services\Profile;

use App\Models\User;
use App\Models\Group;
use Illuminate\Support\Collection;

interface GroupServiceInterface
{
    /**
     * グループ編集画面のデータを返す。
     * @param User $actor
     * @return array{0: ?Group, 1: Collection<int,User>}
     */
    public function getEditData(User $actor): array;

    /**
     * グループを新規作成または名称更新する。
     * @param User $actor
     * @param string $groupName
     * @return string
     */
    public function createOrUpdateGroup(User $actor, string $groupName): string;

    /**
     * メンバーを追加する。
     * @param User $actor
     * @param string $username
     * @param string $email
     * @param string $password
     * @param string|null $name
     * @param bool $canEdit
     * @param bool $privacyConsent
     * @param bool $termsConsent
     * @return User
     */
    public function addMember(User $actor, string $username, string $email, string $password, ?string $name, bool $canEdit, bool $privacyConsent = false, bool $termsConsent = false): User;

    /**
     * メンバーの編集権限を更新する。
     * @param User $actor
     * @param User $member
     * @param bool $canEdit
     * @return void
     */
    public function updateMemberPermission(User $actor, User $member, bool $canEdit): void;

    /**
     * グループマスターを譲渡する。
     * @param User $actor
     * @param User $newMaster
     * @return void
     */
    public function transferMaster(User $actor, User $newMaster): void;

    /**
     * メンバーをグループから外す。
     * @param User $actor
     * @param User $member
     * @return void
     */
    public function removeMember(User $actor, User $member): void;

    /**
     * グループ編集権限があるか確認
     *
     * @param User $user
     * @return bool
     */
    public function canEditGroup(User $user): bool;

    /**
     * メンバーのテーマ設定を切り替える。
     * @param User $actor
     * @param User $member
     * @param bool $theme
     * @return void
     */
    public function toggleMemberTheme(User $actor, User $member, bool $theme): void;

    /**
     * 指定ユーザーのテーマ設定を変更できるか。
     * @param User $actor
     * @param User $member
     * @return bool
     */
    public function canChangeThemeOf(User $actor, User $member): bool;

    /**
     * グループに新しいメンバーを追加できるか確認する。
     * 
     * @param Group $group
     * @return bool
     */
    public function canAddMember(Group $group): bool;

    /**
     * グループの残りメンバー枠数を取得する。
     * 
     * @param Group $group
     * @return int
     */
    public function getRemainingMemberSlots(Group $group): int;

    /**
     * 保護者招待トークン経由での家族グループを作成
     * 
     * @param User $parentUser 保護者ユーザー
     * @param User $childUser 子ユーザー
     * @return Group 作成されたグループ
     * @throws \RuntimeException グループ作成に失敗した場合
     */
    public function createFamilyGroup(User $parentUser, User $childUser): Group;
}