<?php

namespace App\Http\Actions\Task;

use App\Services\Task\TaskManagementServiceInterface;
use App\Services\Task\TaskListServiceInterface;
use App\Services\Tag\TagServiceInterface;
use App\Services\Avatar\TeacherAvatarServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

final class AdoptProposalAction
{
    public function __construct(
        private TaskManagementServiceInterface $taskService,
        private TaskListServiceInterface $taskListService,
        private TagServiceInterface $tagService,
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
            'tasks.*.due_date'  => 'nullable|string',
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
        logger()->info('AdoptProposalAction', ['tasks' => $tasks, 'validated' => $validated, 'request' => $request->all()]);
        try {
            $user = $request->user();
            
            // タスクを一括作成
            $created = $this->taskService->adoptProposal(
                $user,
                $proposalId,
                $tasks
            );

            // タスクとタグを再取得してBlade形式でバケット化
            $allTasks = $this->taskListService->getTasksForUser($user->id, []);
            $allTags = $this->tagService->getByUserId($user->id);
            
            // Bladeテンプレートでバケットレイアウトをレンダリング
            $html = view('dashboard.partials.task-bento', [
                'tasks' => $allTasks,
                'tags' => $allTags,
            ])->render();

            // アバターコメントを取得
            $avatarEventType = config('const.avatar_events.task_breakdown');
            $avatarComment = $this->avatarService->getCommentForEvent(
                $user,
                $avatarEventType
            );

            return response()->json([
                'success' => true,
                'message' => count($created) . '件のタスクを作成しました',
                'tasks' => $created,
                'html' => $html,
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