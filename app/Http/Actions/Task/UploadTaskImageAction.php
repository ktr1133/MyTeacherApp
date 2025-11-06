<?php

namespace App\Http\Actions\Task;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Task\TaskApprovalServiceInterface;

/**
 * タスクの画像をアップロードするアクション。
 */
class UploadTaskImageAction
{
    /**
     * Constructor
     */
    public function __construct(
        private TaskApprovalServiceInterface $taskApprovalService
    ) {}

    /**
     * タスクの画像をアップロードする。
     */
    public function __invoke(Request $request, Task $task)
    {
        if ($task->user_id !== Auth::id()) {
            abort(403);
        }

        $data = $request->validate([
            'image' => ['required', 'image', 'max:5120'], // 5MB
        ]);

        $image = $this->taskApprovalService->uploadImage($task, $data['image']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'image' => $image,
            ]);
        }

        return redirect()->back()->with('success', '画像をアップロードしました。');
    }
}