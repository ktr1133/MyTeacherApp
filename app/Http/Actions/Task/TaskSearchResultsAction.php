<?php

namespace App\Http\Actions\Task;

use App\Http\Responders\Task\TaskSearchResultsResponder;
use App\Services\Task\TaskManagementServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * タスク検索結果表示アクション
 */
class TaskSearchResultsAction
{
    protected TaskManagementServiceInterface $taskManagementService;
    protected TaskSearchResultsResponder $responder;

    public function __construct(
        TaskManagementServiceInterface $taskManagementService,
        TaskSearchResultsResponder $responder
    ) {
        $this->taskManagementService = $taskManagementService;
        $this->responder = $responder;
    }

    /**
     * 検索結果を表示
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        try {
            // バリデーション
            $validated = $request->validate([
                'type' => 'required|in:title,tag',
                'terms' => 'required|array',
                'terms.*' => 'string|max:255',
                'operator' => 'nullable|in:and,or',
            ]);

            $searchType = $validated['type'];
            $searchTerms = $validated['terms'];
            $operator = $validated['operator'] ?? 'or';

            Log::info('Task search results requested', [
                'user_id' => Auth::id(),
                'type' => $searchType,
                'terms' => $searchTerms,
                'operator' => $operator,
            ]);

            // 検索実行
            $tasks = $this->taskManagementService->searchTasks(
                Auth::user(),
                $searchType,
                $searchTerms,
                $operator
            );

            // ソート処理（タグ名昇順 → 期限昇順）
            $sortedTasks = $this->sortTasksForDisplay($tasks);

            // レスポンダーに渡す
            return $this->responder->response($sortedTasks, $validated);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Task search validation failed', [
                'user_id' => Auth::id(),
                'errors' => $e->errors(),
            ]);
            
            return redirect()->route('dashboard')
                ->with('error', '検索条件が正しくありません');

        } catch (\Exception $e) {
            Log::error('Task search results failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('dashboard')
                ->with('error', '検索中にエラーが発生しました');
        }
    }

    /**
     * タスクを表示用にソート
     *
     * @param \Illuminate\Support\Collection $tasks
     * @return \Illuminate\Support\Collection
     */
    private function sortTasksForDisplay($tasks)
    {
        return $tasks->sortBy([
            // 第1ソート: タグ名（タグなしは最後）
            function ($task) {
                $firstTag = $task->tags->first();
                return $firstTag ? $firstTag->name : 'zzz_未分類';
            },
            // 第2ソート: 期限（期限なしは最後）
            function ($task) {
                return $task->due_date ?? '9999-12-31';
            },
        ])->values();
    }
}