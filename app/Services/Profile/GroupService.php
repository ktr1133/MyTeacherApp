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
     * @param string $email
     * @param string $password
     * @param string|null $name
     * @param bool $canEdit
     * @param bool $privacyConsent 保護者によるプライバシーポリシー同意
     * @param bool $termsConsent 保護者による利用規約同意
     * @return User
     */
    public function addMember(User $actor, string $username, string $email, string $password, ?string $name, bool $canEdit, bool $privacyConsent = false, bool $termsConsent = false): User
    {
        $group = $actor->group;
        if (!$group || !$this->canEditGroup($actor)) {
            abort(403, 'グループメンバーを追加する権限がありません。');
        }

        // メンバー数制限チェック
        if (!$this->canAddMember($group)) {
            abort(422, 'グループメンバー数が上限に達しています。サブスクリプションプランをアップグレードしてください。');
        }

        return DB::transaction(function () use ($username, $email, $password, $name, $group, $canEdit, $actor, $privacyConsent, $termsConsent, &$user): User {
            if (User::where('username', $username)->exists()) {
                abort(422, '指定されたユーザー名は既に存在します。');
            }

            // 代理同意の記録準備
            $consentData = [];
            if ($privacyConsent && $termsConsent) {
                $consentData = [
                    'created_by_user_id' => $actor->id,
                    'consent_given_by_user_id' => $actor->id,
                    'privacy_policy_version' => config('legal.current_versions.privacy_policy'),
                    'terms_version' => config('legal.current_versions.terms_of_service'),
                    'privacy_policy_agreed_at' => now(),
                    'terms_agreed_at' => now(),
                ];
            }

            // ユーザー作成＆グループ作成
            return $this->groupUsers->create(array_merge([
                'username' => $username,
                'email' => $email,
                'name' => $name ?? $username, // nameが空の場合はusernameを使用
                'password' => Hash::make($password),
                'group_id' => $group->id,
                'group_edit_flg' => $canEdit,
            ], $consentData));
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
            throw new \Illuminate\Auth\Access\AuthorizationException('グループマスターを譲渡する権限がありません。');
        }
        if ($newMaster->group_id !== $group->id) {
            throw new \Illuminate\Auth\Access\AuthorizationException('他のグループのメンバーには譲渡できません。');
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
            throw new \Illuminate\Auth\Access\AuthorizationException('グループメンバーを削除する権限がありません。');
        }
        if ($member->group_id !== $group->id) {
            throw new \Illuminate\Auth\Access\AuthorizationException('他のグループのメンバーは削除できません。');
        }
        if ($this->isGroupMaster($member)) {
            throw new \Illuminate\Auth\Access\AuthorizationException('グループマスターは削除できません。');
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

    /**
     * グループに新しいメンバーを追加できるか確認する。
     * 
     * @param Group $group
     * @return bool
     */
    public function canAddMember(Group $group): bool
    {
        $currentMemberCount = $group->users()->count();
        return $currentMemberCount < $group->max_members;
    }

    /**
     * グループの残りメンバー枠数を取得する。
     * 
     * @param Group $group
     * @return int
     */
    public function getRemainingMemberSlots(Group $group): int
    {
        $currentMemberCount = $group->users()->count();
        return max(0, $group->max_members - $currentMemberCount);
    }

    /**
     * 保護者招待トークン経由での家族グループを作成
     * 
     * ランダム8文字のグループ名を生成し、保護者をマスターユーザーとして設定。
     * 保護者と子アカウントを同じグループに所属させる。
     * 
     * @param User $parentUser 保護者ユーザー
     * @param User $childUser 子ユーザー
     * @return Group 作成されたグループ
     * @throws \RuntimeException グループ作成に失敗した場合
     */
    public function createFamilyGroup(User $parentUser, User $childUser): Group
    {
        try {
            return DB::transaction(function () use ($parentUser, $childUser) {
                // 子アカウントの既存グループチェック
                if ($childUser->group_id !== null) {
                    throw new \RuntimeException('お子様は既に別のグループに所属しています。');
                }

                // ランダム8文字のグループ名生成（英数字混合）
                $groupName = \Illuminate\Support\Str::random(8);

                // グループ作成
                $group = $this->groups->create([
                    'name' => $groupName,
                    'master_user_id' => $parentUser->id,
                ]);

                // 保護者アカウントにグループ設定
                $this->groupUsers->update($parentUser, [
                    'group_id' => $group->id,
                    'group_edit_flg' => true, // グループ編集権限: ON
                ]);

                // 子アカウントに親子紐付け + グループ参加
                $this->groupUsers->update($childUser, [
                    'parent_user_id' => $parentUser->id,
                    'group_id' => $group->id,
                    'parent_invitation_token' => null, // トークン無効化（再利用防止）
                ]);

                \Illuminate\Support\Facades\Log::info('Family group created via parent invitation', [
                    'group_id' => $group->id,
                    'group_name' => $groupName,
                    'parent_user_id' => $parentUser->id,
                    'parent_username' => $parentUser->username,
                    'child_user_id' => $childUser->id,
                    'child_username' => $childUser->username,
                ]);

                return $group;
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to create family group', [
                'parent_user_id' => $parentUser->id,
                'child_user_id' => $childUser->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \RuntimeException('グループの作成に失敗しました: ' . $e->getMessage(), 0, $e);
        }
    }
}