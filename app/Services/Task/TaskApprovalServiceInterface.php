<?php

namespace App\Services\Task;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

interface TaskApprovalServiceInterface
{
    /**
     * タスクの完了申請
     */
    public function requestApproval(Task $task, User $user): Task;

    /**
     * タスクを承認
     */
    public function approveTask(Task $task, User $approver): Task;

    /**
     * タスクを却下
     */
    public function rejectTask(Task $task, User $approver, ?string $reason): Task;

    /**
     * 承認待ちタスク一覧を取得
     */
    public function getPendingApprovals(User $user): Collection;

    /**
     * タスクに画像をアップロード
     */
    public function uploadImage(Task $task, UploadedFile $file): \App\Models\TaskImage;

    /**
     * タスク画像を削除
     */
    public function deleteImage(\App\Models\TaskImage $image): bool;
}