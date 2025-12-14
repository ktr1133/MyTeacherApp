<?php

namespace App\Http\Actions\Api\GroupTask;

use App\Services\Task\TaskManagementServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * グループタスク更新API Action
 * 
 * モバイルアプリ用: グループタスクの情報を更新
 */
class UpdateGroupTaskApiAction
{
    /**
     * コンストラクタ
     *
     * @param TaskManagementServiceInterface $taskManagementService
     */
    public function __construct(
        protected TaskManagementServiceInterface $taskManagementService
    ) {}

    /**
     * グループタスクを更新
     *
     * @param Request $request
     * @param string $groupTaskId
     * @return JsonResponse
     */
    public function __invoke(Request $request, string $groupTaskId): JsonResponse
    {
        $user = $request->user();
        
        // 権限チェック
        if (!$user->canEditGroup()) {
            return response()->json([
                'message' => 'この操作を実行する権限がありません。',
            ], 403);
        }

        // バリデーション
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'span' => 'required|integer|in:1,3,6',
            'due_date' => 'nullable|string|max:255',
            'reward' => 'required|integer|min:0',
            'requires_approval' => 'required|boolean',
            'requires_image' => 'required|boolean',
        ], [
            'title.required' => 'タイトルは必須です。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'description.max' => '説明は1000文字以内で入力してください。',
            'span.required' => '期間は必須です。',
            'span.integer' => '期間は整数で入力してください。',
            'span.in' => '期間は1（短期）、3（中期）、6（長期）のいずれかを指定してください。',
            'due_date.string' => '期限日は文字列で入力してください。',
            'due_date.max' => '期限日は255文字以内で入力してください。',
            'reward.required' => 'ポイントは必須です。',
            'reward.integer' => 'ポイントは整数で入力してください。',
            'reward.min' => 'ポイントは0以上で入力してください。',
            'requires_approval.required' => '承認要否は必須です。',
            'requires_approval.boolean' => '承認要否の値が不正です。',
            'requires_image.required' => '画像必須の設定は必須です。',
            'requires_image.boolean' => '画像必須の値が不正です。',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'バリデーションエラーが発生しました。',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // グループタスク存在確認
            $groupTask = $this->taskManagementService->findEditableGroupTask($user, $groupTaskId);
            
            if (!$groupTask) {
                return response()->json([
                    'message' => '指定されたグループタスクが見つかりません。',
                ], 404);
            }

            // グループタスク更新
            $updatedCount = $this->taskManagementService->updateGroupTask(
                $user,
                $groupTaskId,
                $validator->validated()
            );
            
            Log::info('[API] グループタスク更新成功', [
                'user_id' => $user->id,
                'group_task_id' => $groupTaskId,
                'updated_count' => $updatedCount,
            ]);

            // 更新後のデータを再取得
            $updatedTask = $this->taskManagementService->findEditableGroupTask($user, $groupTaskId);

            // レスポンス整形（$updatedTaskは配列）
            // due_dateは文字列（長期）またはCarbonインスタンス（短期・中期）の可能性がある
            $dueDate = $updatedTask['due_date'];
            if ($dueDate instanceof \Carbon\Carbon) {
                $dueDate = $dueDate->format('Y-m-d');
            }
            
            $data = [
                'group_task_id' => $updatedTask['group_task_id'],
                'title' => $updatedTask['title'],
                'description' => $updatedTask['description'],
                'span' => $updatedTask['span'],
                'reward' => $updatedTask['reward'],
                'due_date' => $dueDate,
                'requires_approval' => (bool) $updatedTask['requires_approval'],
                'requires_image' => (bool) $updatedTask['requires_image'],
                'assigned_count' => $updatedTask['assigned_count'] ?? 0,
                'updated_at' => $updatedTask['updated_at']?->toIso8601String(),
            ];

            return response()->json([
                'message' => 'グループタスクを更新しました。',
                'data' => $data,
            ]);
            
        } catch (\Exception $e) {
            Log::error('[API] グループタスク更新エラー', [
                'user_id' => $user->id,
                'group_task_id' => $groupTaskId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'グループタスクの更新に失敗しました。',
            ], 500);
        }
    }
}
