<?php

namespace App\Services\Group;

use App\Models\Group;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * グループタスク作成制限サービス
 * 
 * グループタスクの無料作成回数制限と
 * サブスクリプション状態に基づくアクセス制御を提供
 */
class GroupTaskLimitService implements GroupTaskLimitServiceInterface
{
    /**
     * @inheritDoc
     */
    public function canCreateGroupTask(Group $group): bool
    {
        // サブスクリプションが有効な場合は制限なし
        if ($group->subscription_active) {
            return true;
        }

        // 月次カウントが期限切れの場合はリセット
        if ($this->shouldResetCount($group)) {
            $this->resetMonthlyCount($group);
            $group->refresh();
        }

        // 無料枠を超えていないか確認
        return $group->group_task_count_current_month < $group->free_group_task_limit;
    }

    /**
     * @inheritDoc
     */
    public function incrementGroupTaskCount(Group $group): void
    {
        // サブスクリプションが有効な場合はカウントしない
        if ($group->subscription_active) {
            return;
        }

        DB::transaction(function () use ($group) {
            // 月次カウントが期限切れの場合はリセット
            if ($this->shouldResetCount($group)) {
                $this->resetMonthlyCount($group);
            } else {
                // カウントを増加
                $group->increment('group_task_count_current_month');
            }

            Log::info('Group task count incremented', [
                'group_id' => $group->id,
                'current_count' => $group->group_task_count_current_month + 1,
                'limit' => $group->free_group_task_limit,
            ]);
        });
    }

    /**
     * @inheritDoc
     */
    public function resetMonthlyCount(Group $group): void
    {
        DB::transaction(function () use ($group) {
            $group->update([
                'group_task_count_current_month' => 0,
                'group_task_count_reset_at' => $this->getNextResetDate(),
            ]);

            Log::info('Group task count reset', [
                'group_id' => $group->id,
                'reset_at' => $group->group_task_count_reset_at,
            ]);
        });
    }

    /**
     * @inheritDoc
     */
    public function getGroupTaskUsage(Group $group): array
    {
        // 月次カウントが期限切れの場合は0として扱う
        $current = $this->shouldResetCount($group) ? 0 : $group->group_task_count_current_month;

        return [
            'current' => $current,
            'limit' => $group->free_group_task_limit,
            'remaining' => max(0, $group->free_group_task_limit - $current),
            'has_subscription' => $group->subscription_active,
            'reset_at' => $group->group_task_count_reset_at,
        ];
    }

    /**
     * カウントをリセットすべきか判定
     * 
     * @param Group $group
     * @return bool
     */
    protected function shouldResetCount(Group $group): bool
    {
        // リセット日時が未設定、または過去の場合はリセット
        return !$group->group_task_count_reset_at 
            || Carbon::now()->greaterThanOrEqualTo($group->group_task_count_reset_at);
    }

    /**
     * 次回のリセット日時を取得（翌月1日 00:00:00）
     * 
     * @return Carbon
     */
    protected function getNextResetDate(): Carbon
    {
        return Carbon::now()->startOfMonth()->addMonth();
    }
}
