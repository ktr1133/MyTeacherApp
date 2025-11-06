<?php

namespace App\Http\Actions\Task;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

/**
 * タスクの完了状態をトグルするアクション。
 * - ブラウザの通常フォーム送信の場合はリダイレクトで戻す
 * - AJAX / JSON要求の場合は JSON を返す
 */
class ToggleTaskCompletionAction
{
    /**
     * @param Task $task ルートモデルバインディングで注入される Task
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function __invoke(Task $task, Request $request)
    {
        // 所有者チェック（ポリシーがあればそちらを使ってください）
        $user = $request->user();
        if ($user === null || $task->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        // トグルして保存
        $task->is_completed = (bool) ! $task->is_completed;
        $task->completed_at = $task->is_completed ? now() : null;
        $task->save();

        // JSON リクエストなら JSON を返す
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'task_id' => $task->id,
                'is_completed' => (bool) $task->is_completed,
            ]);
        }

        // 通常リクエストは前のページへリダイレクトしてメッセージを添える
        $message = $task->is_completed ? 'タスクを完了にしました。' : 'タスクを未完了に戻しました。';
        return redirect()->back()->with('success', $message);
    }
}