<?php

namespace App\Http\Actions\Api\Task;

use App\Models\Task;
use Illuminate\Http\Request;
use App\Services\Task\TaskManagementServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * API: タスク更新アクション
 * 
 * モバイルアプリからのタスク更新リクエストを処理
 * Cognito認証を前提（middleware: cognito）
 */
class UpdateTaskApiAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected TaskManagementServiceInterface $taskService
    ) {}

    /**
     * タスクを更新
     *
     * @param Request $request リクエスト
     * @param Task $task ルートモデルバインディング
     * @return JsonResponse
     */
    public function __invoke(Request $request, Task $task): JsonResponse
    {
        try {
            // 認証済みユーザーを取得（VerifyCognitoTokenで注入済み）
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'ユーザー認証に失敗しました。',
                ], 401);
            }

            // 所有権チェック
            if ($task->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'このタスクを更新する権限がありません。',
                ], 403);
            }
            
            // バリデーション（spanは任意）
            // due_dateは中期タスク（span=2）で年のみ（例: "2025"）を許容するためstring
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'span' => 'nullable|integer|min:1',
                'due_date' => 'nullable|string',
                'priority' => 'sometimes|integer|min:1|max:5',
                'reward' => 'nullable|integer|min:0',
                'requires_approval' => 'sometimes|boolean',
                'requires_image' => 'sometimes|boolean',
                'tag_ids' => 'nullable|array',
                'tag_ids.*' => 'integer|exists:tags,id',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            // バリデーション済みデータを取得
            $validatedData = $validator->validated();
            
            // tag_ids を tags に変換（TaskManagementService用）
            if (array_key_exists('tag_ids', $validatedData)) {
                $validatedData['tags'] = $validatedData['tag_ids'];
                unset($validatedData['tag_ids']);
            }

            // タスクを更新
            $updatedTask = $this->taskService->updateTask($task, $validatedData);

            // レスポンス（タグ情報を含める）
            return response()->json([
                'success' => true,
                'message' => 'タスクを更新しました。',
                'data' => [
                    'task' => [
                        'id' => $updatedTask->id,
                        'title' => $updatedTask->title,
                        'description' => $updatedTask->description,
                        'span' => $updatedTask->span,
                        'due_date' => $updatedTask->hasParsableDueDate() ? $updatedTask->due_date->format('Y-m-d') : $updatedTask->due_date,
                        'priority' => $updatedTask->priority,
                        'status' => $updatedTask->status,
                        'tags' => $updatedTask->tags->map(function ($tag) {
                            return [
                                'id' => $tag->id,
                                'name' => $tag->name,
                                'color' => $tag->color,
                            ];
                        })->toArray(),
                        'updated_at' => $updatedTask->updated_at->toIso8601String(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('API: タスク更新エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'task_id' => $task->id,
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'サーバーエラーが発生しました。',
            ], 500);
        }
    }
}
