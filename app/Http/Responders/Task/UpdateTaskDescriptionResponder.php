<?php

namespace App\Http\Responders\Task;

use App\Models\Task;
use Illuminate\Http\RedirectResponse;

/**
 * タスク説明文更新のレスポンダー
 */
class UpdateTaskDescriptionResponder
{
    /**
     * 成功レスポンス
     *
     * @param Task $task 更新されたタスク
     * @return RedirectResponse
     */
    public function success(Task $task): RedirectResponse
    {
        return redirect()
            ->route('tasks.pending-approvals')
            ->with('success', 'タスクを更新しました');
    }

    /**
     * エラーレスポンス
     *
     * @param string $errorMessage エラーメッセージ
     * @return RedirectResponse
     */
    public function error(string $errorMessage): RedirectResponse
    {
        return redirect()
            ->back()
            ->withInput()
            ->with('error', $errorMessage);
    }
}