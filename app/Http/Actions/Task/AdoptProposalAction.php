<?php

namespace App\Http\Actions\Task;

use App\Services\Task\TaskManagementServiceInterface;
use App\Services\Avatar\TeacherAvatarServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

final class AdoptProposalAction
{
    public function __construct(
        private TaskManagementServiceInterface $taskService,
        private readonly TeacherAvatarServiceInterface $avatarService,
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
        $proposalId = (int) $validated['proposal_id'];
        $tasks = array_map(function ($t) {
            return [
                'title' => (string) $t['title'],
                'span' => $t['span'],
                'priority' => isset($t['priority']) ? (int) $t['priority'] : null,
                'due_date' => $t['due_date'] ?? null,
                'tags' => array_values(
                    array_filter(
                        array_map('strval', $t['tags'] ?? []),
                        fn ($s) => $s !== ''
                    )
                ),
            ];
        }, $validated['tasks']);

        try {
            // タスクを一括作成
            $created = $this->taskService->adoptProposal(
                $request->user(),
                $proposalId,
                $tasks
            );

            // アバターコメントを取得
            $avatarEventType = config('const.avatar_events.task_breakdown');
            $avatarComment = $this->avatarService->getCommentForEvent(
                $request->user(),
                $avatarEventType
            );

            return response()->json([
                'success' => true,
                'message' => count($created) . '件のタスクを作成しました',
                'tasks' => $created,
                'avatar_comment' => $avatarComment,
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