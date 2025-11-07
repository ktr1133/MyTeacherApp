<?php

namespace App\Services\Batch;

use App\Models\ScheduledGroupTask;

interface ScheduledTaskServiceInterface
{
    /**
     * 今日実行すべきスケジュールタスクを処理
     *
     * @param \DateTime|null $date
     * @return array ['success' => int, 'failed' => int, 'skipped' => int]
     */
    public function executeScheduledTasks(?\DateTime $date = null): array;

    /**
     * 個別のスケジュールタスクを実行
     *
     * @param ScheduledGroupTask $scheduledTask
     * @param \DateTime|null $date
     * @return string 'success'|'failed'|'skipped'
     */
    public function executeScheduledTask(ScheduledGroupTask $scheduledTask, ?\DateTime $date = null): string;

    /**
     * スケジュールタスクを作成
     *
     * @param array $data
     * @return ScheduledGroupTask
     */
    public function createScheduledTask(array $data): ScheduledGroupTask;

    /**
     * スケジュールタスクを更新
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateScheduledTask(int $id, array $data): bool;

    /**
     * スケジュールタスクを削除
     *
     * @param int $id
     * @return bool
     */
    public function deleteScheduledTask(int $id): bool;

    /**
     * スケジュールタスクを一時停止
     *
     * @param int $id
     * @return bool
     */
    public function pauseScheduledTask(int $id): bool;

    /**
     * スケジュールタスクを再開
     *
     * @param int $id
     * @return bool
     */
    public function resumeScheduledTask(int $id): bool;
}