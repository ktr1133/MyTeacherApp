<?php

namespace App\Http\Actions\Task;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\Task\TaskProposalServiceInterface;

class ProposeTaskAction
{
    public function __construct(
        private TaskProposalServiceInterface $proposalService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            // バリデーション
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'span' => 'required|integer|min:1|max:3',
                'due_date' => 'nullable|string',
                'context' => 'nullable|string',
                'is_refinement' => 'boolean',
            ]);

            if ($validator->fails()) {
                Log::warning('Validation failed in ProposeTaskAction', [
                    'errors' => $validator->errors()->toArray(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'バリデーションエラー',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();
            $user = $request->user();

            // 提案生成
            $proposal = $this->proposalService->createProposal(
                $request->user(),
                $validated['title'],
                $validated['span'],
                $validated['context'] ?? null,
                (bool)($validated['is_refinement'] ?? false)
            );

            return response()->json([
                'success' => true,
                'proposal_id' => $proposal->id,
                'original_task' => $proposal->original_task_text,
                'proposed_tasks' => $proposal->proposed_tasks_json,
                'model_used' => $proposal->model_used,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'AI提案の生成に失敗しました',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}