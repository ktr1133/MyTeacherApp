<?php

namespace App\Repositories\Report;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportEloquentRepository implements ReportRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getNormalCompletedCountsByDate(int $userId, Carbon $start, Carbon $end): array
    {
        $rows = Task::where('user_id', $userId)
            ->where('requires_approval', false)
            ->where('is_completed', true)
            ->whereBetween('completed_at', [$start, $end])
            ->selectRaw('DATE(completed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $rows->pluck('count', 'date')->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalIncompleteCountsByDueDate(int $userId, Carbon $start, Carbon $end): array
    {
        $rows = Task::where('user_id', $userId)
            ->where('requires_approval', false)
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($q) {
                $q->where('is_completed', false)->orWhereNull('completed_at');
            })
            ->selectRaw('DATE(due_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $rows->pluck('count', 'date')->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupCompletedCountsByDate(int $userId, Carbon $start, Carbon $end): array
    {
        $rows = Task::where('user_id', $userId)
            ->whereNotNull('group_task_id')
            ->whereNotNull('approved_at')
            ->whereBetween('approved_at', [$start, $end])
            ->selectRaw('DATE(approved_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $rows->pluck('count', 'date')->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupIncompleteCountsByDueDate(int $userId, Carbon $start, Carbon $end): array
    {
        $rows = Task::where('user_id', $userId)
            ->where('requires_approval', true)
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->whereNull('approved_at')
            ->selectRaw('DATE(due_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $rows->pluck('count', 'date')->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupRewardByDate(int $userId, Carbon $start, Carbon $end): array
    {
        $rows = Task::where('user_id', $userId)
            ->where('requires_approval', true)
            ->whereNotNull('approved_at')
            ->whereBetween('approved_at', [$start, $end])
            ->selectRaw('DATE(approved_at) as date, SUM(reward) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $rows->pluck('total', 'date')->toArray();
    }
}