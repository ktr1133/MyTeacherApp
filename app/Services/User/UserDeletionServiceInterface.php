<?php

namespace App\Services\User;

use App\Models\User;

/**
 * ユーザー削除サービスインターフェース
 */
interface UserDeletionServiceInterface
{
    /**
     * ユーザーがグループマスターかどうかを確認
     *
     * @param User $user
     * @return bool
     */
    public function isGroupMaster(User $user): bool;

    /**
     * グループマスターのサブスクリプション状況を取得
     *
     * @param User $user
     * @return array{has_subscription: bool, plan: string|null, members_count: int}
     */
    public function getGroupMasterStatus(User $user): array;

    /**
     * ユーザーを削除（グループマスター以外）
     * - サブスクリプションがあれば解約（期間終了時）
     * - アバター削除
     * - ユーザー削除
     *
     * @param User $user
     * @return void
     * @throws \RuntimeException グループマスターの場合
     */
    public function deleteUser(User $user): void;

    /**
     * グループマスターとグループ全体を削除
     * - サブスクリプション即時解約
     * - 全メンバー削除
     * - グループ削除
     *
     * @param User $user
     * @return void
     * @throws \RuntimeException グループマスターでない場合
     */
    public function deleteGroupMasterAndGroup(User $user): void;
}
