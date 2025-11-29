<?php

namespace App\Http\Actions\Api\Task;

use App\Services\Task\TaskSearchServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * API: タスク検索アクション
 * 
 * タイトル/タグによる検索をサポート
 * 完了済みタスクも含めて検索可能（履歴検索対応）
 * AND/OR演算子に対応
 * Cognito認証を前提（middleware: cognito）
 */
class SearchTasksApiAction
{
    /**
     * コンストラクタ
     *
     * @param TaskSearchServiceInterface $taskSearchService タスク検索サービス
     */
    public function __construct(
        private TaskSearchServiceInterface $taskSearchService
    ) {}

    /**
     * タスク検索を実行
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            // 認証済みユーザーを取得
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'ユーザー認証に失敗しました。',
                ], 401);
            }

            // バリデーション
            $validated = $request->validate([
                'type' => 'required|in:title,tag',
                'operator' => 'required|in:and,or',
                'terms' => 'required|array|min:1',
                'terms.*' => 'string|max:255',
            ]);

            // 検索実行
            $tasks = $this->taskSearchService->search(
                $user->id,
                $validated['type'],
                $validated['operator'],
                $validated['terms']
            );

            Log::info('[SearchTasksApiAction] Task search executed', [
                'user_id' => $user->id,
                'type' => $validated['type'],
                'operator' => $validated['operator'],
                'terms_count' => count($validated['terms']),
                'result_count' => count($tasks),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'tasks' => $tasks,
                    'search_params' => [
                        'type' => $validated['type'],
                        'operator' => $validated['operator'],
                        'terms' => $validated['terms'],
                    ],
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[SearchTasksApiAction] Validation failed', [
                'user_id' => $request->user()?->id,
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラー',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[SearchTasksApiAction] Search failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '検索中にエラーが発生しました。',
            ], 500);
        }
    }
}
