<?php

namespace App\Http\Actions\Task;

use App\Services\Task\TaskManagementServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

final class AdoptProposalAction
{
    public function __construct(
        private TaskManagementServiceInterface $taskService,
    ) {}

    /**
     * AI 提案を採用してタスクを一括作成
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'proposal_id' => 'required|integer|exists:task_proposals,id',
            'tasks' => 'required|array|min:1',
            'tasks.*.title' => 'required|string|max:255',
            'tasks.*.span' => 'required|integer|in:1,2,3',
            'tasks.*.priority' => 'nullable|integer|min:1|max:3',
            'tasks.*.tags'  => 'nullable|array',
            'tasks.*.due_to'  => 'nullable|string',
            'tasks.*.tags.*' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'バリデーションエラー',
                'messages' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        logger()->info('AdoptProposalAction input', $validated);
        logger()->info('adoptProposalAction input before validate', $request->all());   
        // 型を整える（安全側）
        $proposalId = (int) $validated['proposal_id'];
        $tasks = array_map(function ($t) {
            return [
                'title' => (string) $t['title'],
                'span' => $t['span'],
                'priority' => isset($t['priority']) ? (int) $t['priority'] : null,
                'tags' => array_values(
                    array_filter(
                        array_map('strval', $t['tags'] ?? []),
                        fn ($s) => $s !== ''
                    )
                ),
            ];
        }, $validated['tasks']);

        try {
            $created = $this->taskService->adoptProposal(
                $request->user(),
                $proposalId,
                $tasks
            );

            return response()->json([
                'success' => true,
                'message' => count($created) . '件のタスクを作成しました',
                'tasks' => $created,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'タスクの作成に失敗しました',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}