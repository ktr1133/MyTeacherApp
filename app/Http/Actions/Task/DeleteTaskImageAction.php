<?php

namespace App\Http\Actions\Task;

use App\Models\TaskImage;
use Illuminate\Support\Facades\Auth;
use App\Services\Task\TaskApprovalServiceInterface;

/**
 * タスクの画像を削除するアクション。
 */
class DeleteTaskImageAction
{
    /**
     * Constructor
     */
    public function __construct(
        private TaskApprovalServiceInterface $taskApprovalService
    ) {}

    /**
     * タスクの画像を削除する。
     */
    public function __invoke(TaskImage $image)
    {
        if ($image->task->user_id !== Auth::id()) {
            abort(403);
        }

        $this->taskApprovalService->deleteImage($image);

        return redirect()->back()->with('success', '画像を削除しました。');
    }
}