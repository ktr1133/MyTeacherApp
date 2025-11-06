<?php

namespace App\Http\Actions\Task;

use App\Services\Task\TaskSearchServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchTasksAction
{
    public function __construct(
        private TaskSearchServiceInterface $taskSearchService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:title,tag',
            'operator' => 'required|in:and,or',
            'terms' => 'required|array|min:1',
            'terms.*' => 'string|max:255',
        ]);

        try {
            $tasks = $this->taskSearchService->search(
                $request->user()->id,
                $validated['type'],
                $validated['operator'],
                $validated['terms']
            );

            return response()->json([
                'success' => true,
                'tasks' => $tasks,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => '検索中にエラーが発生しました',
            ], 500);
        }
    }
}