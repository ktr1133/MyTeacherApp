<?php

namespace App\Repositories\Batch;

use App\Models\ScheduledGroupTask;
use App\Models\ScheduledTaskExecution;
use Illuminate\Support\Collection;

interface ScheduledTaskRepositoryInterface
{
    /**
     * 実行すべきスケジュールタスクを取得
     *
     * @param \DateTime|null $date
     * @return Collection<ScheduledGroupTask>
     */
    public function getTasksShouldRunData(?\DateTime $date = null): Collection;

    /**
     * IDでスケジュールタスクを取得
     *
     * @param int $id
     * @return ScheduledGroupTask|null
     */
    public function findById(int $id): ?ScheduledGroupTask;

    /**
     * グループIDでスケジュールタスクを取得
     *
     * @param int $groupId
     * @return Collection<ScheduledGroupTask>
     */
    public function getByGroupId(int $groupId): Collection;

    /**
     * アクティブなスケジュールタスクを取得
     *
     * @param int $groupId
     * @return Collection<ScheduledGroupTask>
     */
    public function getActiveByGroupId(int $groupId): Collection;

    /**
     * 全てのアクティブなスケジュールタスクを取得
     *
     * @return Collection<ScheduledGroupTask>
     */
    public function getAllActive(): Collection;

    /**
     * スケジュールタスクを作成
     *
     * @param array $data
     * @return ScheduledGroupTask
     */
    public function create(array $data): ScheduledGroupTask;

    /**
     * スケジュールタスクを更新
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * スケジュールタスクを削除
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * スケジュールタスクを一時停止
     *
     * @param int $id
     * @return bool
     */
    public function pause(int $id): bool;

    /**
     * スケジュールタスクを再開
     *
     * @param int $id
     * @return bool
     */
    public function resume(int $id): bool;

    /**
     * 今日既に実行済みかチェック
     *
     * @param int $scheduledTaskId
     * @param \DateTime $date
     * @return bool
     */
    public function isAlreadyExecutedToday(int $scheduledTaskId, \DateTime $date): bool;

    /**
     * 最後の成功実行を取得
     *
     * @param int $scheduledTaskId
     * @return ScheduledTaskExecution|null
     */
    public function getLastSuccessfulExecution(int $scheduledTaskId): ?ScheduledTaskExecution;

    /**
     * タグを紐付け
     *
     * @param int $scheduledTaskId
     * @param array $tagNames
     * @return void
     */
    public function syncTags(int $scheduledTaskId, array $tagNames): void;

    /**
     * 実行履歴を記録
     *
     * @param array $data
     * @return ScheduledTaskExecution
     */
    public function recordExecution(array $data): ScheduledTaskExecution;

    /**
     * 実行履歴を取得
     *
     * @param int $scheduledTaskId
     * @param int $limit
     * @return Collection<ScheduledTaskExecution>
     */
    public function getExecutionHistory(int $scheduledTaskId, int $limit = 50): Collection;
}