<?php

namespace App\Services\Group;

use App\Models\Group;

/**
 * グループタスク作成制限サービスインターフェース
 */
interface GroupTaskLimitServiceInterface
{
    /**
     * グループタスクを作成できるか確認
     * 
     * @param Group $group
     * @return bool
     */
    public function canCreateGroupTask(Group $group): bool;

    /**
     * グループタスク作成回数を増加
     * 
     * @param Group $group
     * @return void
     */
    public function incrementGroupTaskCount(Group $group): void;

    /**
     * 月次カウントをリセット
     * 
     * @param Group $group
     * @return void
     */
    public function resetMonthlyCount(Group $group): void;

    /**
     * グループの現在の使用状況を取得
     * 
     * @param Group $group
     * @return array ['current' => int, 'limit' => int, 'remaining' => int, 'has_subscription' => bool]
     */
    public function getGroupTaskUsage(Group $group): array;
}
