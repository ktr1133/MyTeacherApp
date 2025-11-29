<?php

namespace App\Repositories\Batch;

use App\Models\ScheduledGroupTask;
use App\Models\ScheduledTaskExecution;
use App\Models\ScheduledTaskTag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ScheduledTaskRepository implements ScheduledTaskRepositoryInterface
{
    /**
     * 実行すべきスケジュールタスクを取得
     */
    public function getTasksShouldRunData(?\DateTime $date = null): Collection
    {
        $date = $date ?? now();
        
        return ScheduledGroupTask::with([
                'group',           // グループ情報
                'group.users',     // グループメンバー（ランダム割り当て用）
                'tags',            // タグ情報
                'assignedUser',    // 担当者情報
                'creator'          // 作成者情報
            ])
            ->active()
            ->where('start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $date);
            })
            ->get();
    }

    /**
     * IDでスケジュールタスクを取得
     */
    public function findById(int $id): ?ScheduledGroupTask
    {
        return ScheduledGroupTask::with(['group', 'tags', 'assignedUser', 'creator'])
            ->find($id);
    }

    /**
     * グループIDでスケジュールタスクを取得
     */
    public function getByGroupId(int $groupId): Collection
    {
        return ScheduledGroupTask::with(['tags', 'assignedUser', 'creator'])
            ->where('group_id', $groupId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * アクティブなスケジュールタスクを取得
     */
    public function getActiveByGroupId(int $groupId): Collection
    {
        return ScheduledGroupTask::with(['tags', 'assignedUser', 'creator'])
            ->where('group_id', $groupId)
            ->active()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * 全てのアクティブなスケジュールタスクを取得
     */
    public function getAllActive(): Collection
    {
        return ScheduledGroupTask::with(['group', 'tags', 'assignedUser', 'creator'])
            ->active()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * スケジュールタスクを作成
     */
    public function create(array $data): ScheduledGroupTask
    {
        return DB::transaction(function () use ($data) {
            $tags = $data['tags'] ?? [];
            unset($data['tags']);

            $scheduledTask = ScheduledGroupTask::create($data);

            if (!empty($tags)) {
                $this->syncTags($scheduledTask->id, $tags);
            }

            return $scheduledTask->load(['tags', 'assignedUser', 'creator']);
        });
    }

    /**
     * スケジュールタスクを更新
     */
    public function update(int $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $scheduledTask = ScheduledGroupTask::findOrFail($id);
            
            $tags = $data['tags'] ?? null;
            unset($data['tags']);

            $result = $scheduledTask->update($data);

            if ($tags !== null) {
                $this->syncTags($id, $tags);
            }

            return $result;
        });
    }

    /**
     * スケジュールタスクを削除
     */
    public function delete(int $id): bool
    {
        $scheduledTask = ScheduledGroupTask::findOrFail($id);
        return $scheduledTask->delete();
    }

    /**
     * スケジュールタスクを一時停止
     */
    public function pause(int $id): bool
    {
        $scheduledTask = ScheduledGroupTask::findOrFail($id);
        return $scheduledTask->pause();
    }

    /**
     * スケジュールタスクを再開
     */
    public function resume(int $id): bool
    {
        $scheduledTask = ScheduledGroupTask::findOrFail($id);
        return $scheduledTask->resume();
    }

    /**
     * 今日既に実行済みかチェック
     */
    public function isAlreadyExecutedToday(int $scheduledTaskId, \DateTime $date): bool
    {
        $startOfDay = Carbon::parse($date)->startOfDay();
        $endOfDay = Carbon::parse($date)->endOfDay();

        return ScheduledTaskExecution::where('scheduled_task_id', $scheduledTaskId)
            ->whereBetween('executed_at', [$startOfDay, $endOfDay])
            ->where('status', 'success')
            ->exists();
    }

    /**
     * 最後の成功実行を取得
     */
    public function getLastSuccessfulExecution(int $scheduledTaskId): ?ScheduledTaskExecution
    {
        return ScheduledTaskExecution::where('scheduled_task_id', $scheduledTaskId)
            ->whereNotNull('created_task_id')
            ->where('status', 'success')
            ->latest('executed_at')
            ->first();
    }

    /**
     * タグを紐付け
     */
    public function syncTags(int $scheduledTaskId, array $tagNames): void
    {
        // 既存のタグを削除
        ScheduledTaskTag::where('scheduled_task_id', $scheduledTaskId)->delete();

        // 新しいタグを追加
        foreach ($tagNames as $tagName) {
            ScheduledTaskTag::create([
                'scheduled_task_id' => $scheduledTaskId,
                'tag_name' => $tagName,
            ]);
        }
    }

    /**
     * 実行履歴を記録
     */
    public function recordExecution(array $data): ScheduledTaskExecution
    {
        return ScheduledTaskExecution::create($data);
    }

    /**
     * 実行履歴を取得
     */
    public function getExecutionHistory(int $scheduledTaskId, int $limit = 50): Collection
    {
        return ScheduledTaskExecution::with(['createdTask', 'deletedTask'])
            ->where('scheduled_task_id', $scheduledTaskId)
            ->orderBy('executed_at', 'desc')
            ->limit($limit)
            ->get();
    }
}