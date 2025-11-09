<?php

namespace App\Http\Actions\Task;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\Task\TaskProposalServiceInterface;
use App\Services\Token\TokenServiceInterface;

/**
 * タスク提案アクション
 */
class ProposeTaskAction
{
    public function __construct(
        private TaskProposalServiceInterface $proposalService,
        private TokenServiceInterface $tokenService
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

            // トークン残高チェック（推定1000トークン）
            $estimatedTokens = 1000;
            if (!$this->tokenService->checkBalance($user, $estimatedTokens)) {
                return response()->json([
                    'success' => false,
                    'error' => 'トークン残高不足',
                    'message' => 'トークン残高が不足しています。トークンを購入してください。',
                    'action_url' => route('tokens.purchase'),
                ], 402); // 402 Payment Required
            }

            // 提案生成
            $proposal = $this->proposalService->createProposal(
                $user,
                $validated['title'],
                (string)$validated['span'],
                $validated['context'] ?? null,
                (bool)($validated['is_refinement'] ?? false)
            );

            Log::info('Task proposal created', [
                'user_id' => $user->id,
                'proposal_id' => $proposal->id,
                'tokens_used' => $proposal->total_tokens,
            ]);

            return response()->json([
                'success' => true,
                'proposal_id' => $proposal->id,
                'original_task' => $proposal->original_task_text,
                'proposed_tasks' => $proposal->proposed_tasks_json,
                'model_used' => $proposal->model_used,
                'tokens_used' => [
                    'prompt' => $proposal->prompt_tokens,
                    'completion' => $proposal->completion_tokens,
                    'total' => $proposal->total_tokens,
                ],
            ]);
        } catch (\RuntimeException $e) {
            // トークン不足などのビジネスロジックエラー
            if (str_contains($e->getMessage(), 'トークン残高')) {
                return response()->json([
                    'success' => false,
                    'error' => 'トークン残高不足',
                    'message' => $e->getMessage(),
                    'action_url' => route('tokens.purchase'),
                ], 402);
            }

            Log::error('Task proposal failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'AI提案の生成に失敗しました',
                'message' => $e->getMessage(),
            ], 500);
        } catch (\Throwable $e) {
            Log::error('Unexpected error in ProposeTaskAction', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'AI提案の生成に失敗しました',
                'message' => '予期しないエラーが発生しました',
            ], 500);
        }
    }
}