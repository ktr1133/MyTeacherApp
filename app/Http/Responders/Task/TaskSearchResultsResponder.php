<?php

namespace App\Http\Responders\Task;

use App\Repositories\Task\TaskRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Http\Response;

/**
 * タスク検索結果のレスポンダー
 */
class TaskSearchResultsResponder
{
    protected TaskRepositoryInterface $taskRepository;

    public function __construct(TaskRepositoryInterface $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    /**
     * 検索結果画面を表示
     *
     * @param Collection $tasks
     * @param array $searchParams
     * @return \Illuminate\Http\Response
     */
    public function response(Collection $tasks, array $searchParams): Response
    {
        // 全タグを取得（モーダルで使用）
        $allTags = $this->taskRepository->getAllTags();

        return response()->view('tasks.search-results', [
            'tasks' => $tasks,
            'searchType' => $searchParams['type'],
            'searchTerms' => $searchParams['terms'],
            'operator' => $searchParams['operator'] ?? 'or',
            'totalCount' => $tasks->count(),
            'allTags' => $allTags,
        ]);
    }
}