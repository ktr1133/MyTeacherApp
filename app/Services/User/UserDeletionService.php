<?php

namespace App\Services\User;

use App\Models\User;
use App\Models\Group;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ユーザー削除サービス
 * 
 * アカウント削除時のサブスクリプション解約、
 * グループマスター削除時のグループ削除処理を担当
 */
class UserDeletionService implements UserDeletionServiceInterface
{
    /**
     * ユーザーがグループマスターかどうかを確認
     *
     * @param User $user
     * @return bool
     */
    public function isGroupMaster(User $user): bool
    {
        if (!$user->group_id) {
            return false;
        }

        $group = Group::find($user->group_id);
        return $group && $group->master_user_id === $user->id;
    }

    /**
     * グループマスターのサブスクリプション状況を取得
     *
     * @param User $user
     * @return array{has_subscription: bool, plan: string|null, members_count: int}
     */
    public function getGroupMasterStatus(User $user): array
    {
        if (!$this->isGroupMaster($user)) {
            return [
                'has_subscription' => false,
                'plan' => null,
                'members_count' => 0,
            ];
        }

        $group = Group::find($user->group_id);
        $subscription = $group->subscription('default');

        return [
            'has_subscription' => $subscription && $subscription->active(),
            'plan' => $group->subscription_plan,
            'members_count' => $group->users()->count(),
        ];
    }

    /**
     * ユーザーを削除（グループマスター以外）
     *
     * @param User $user
     * @return void
     * @throws \RuntimeException グループマスターの場合
     */
    public function deleteUser(User $user): void
    {
        if ($this->isGroupMaster($user)) {
            throw new \RuntimeException('グループマスターは単独で削除できません。先にマスター権限を譲渡するか、グループ全体を削除してください。');
        }

        DB::transaction(function () use ($user) {
            // アバター削除
            if (!empty($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            // ユーザー削除
            $user->delete();

            Log::info('User deleted', [
                'user_id' => $user->id,
                'username' => $user->username,
            ]);
        });
    }

    /**
     * グループマスターとグループ全体を削除
     *
     * @param User $user
     * @return void
     * @throws \RuntimeException グループマスターでない場合
     */
    public function deleteGroupMasterAndGroup(User $user): void
    {
        if (!$this->isGroupMaster($user)) {
            throw new \RuntimeException('グループマスターではありません。');
        }

        DB::transaction(function () use ($user) {
            $group = Group::find($user->group_id);

            // サブスクリプション解約（即時）
            $subscription = $group->subscription('default');
            if ($subscription && $subscription->active()) {
                $subscription->cancelNow();
                Log::info('Subscription canceled due to group master deletion', [
                    'group_id' => $group->id,
                    'subscription_id' => $subscription->id,
                ]);
            }

            // 全メンバーのアバター削除とユーザー削除
            $members = $group->users;
            foreach ($members as $member) {
                if (!empty($member->avatar_path)) {
                    Storage::disk('public')->delete($member->avatar_path);
                }
                $member->delete();
            }

            // グループ削除
            $group->delete();

            Log::info('Group and all members deleted', [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'members_count' => $members->count(),
                'deleted_by' => $user->id,
            ]);
        });
    }
}
