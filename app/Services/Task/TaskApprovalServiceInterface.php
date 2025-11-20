<?php
// filepath: /home/ktr/mtdev/laravel/app/Services/Task/TaskApprovalServiceInterface.php

namespace App\Services\Task;

use App\Models\Task;
use App\Models\TaskImage;
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
     * 承認者として承認待ちタスク一覧を取得（統合画面用）
     */
    public function getPendingTasksForApprover(User $approver): Collection;

    /**
     * タスクに画像をアップロード
     */
    public function uploadImage(Task $task, UploadedFile $file): TaskImage;

    /**
     * タスク画像を削除
     */
    public function deleteImage(TaskImage $image): bool;

    /**
     * タスクを承認不要で完了する。
     * @param Task $task
     * @param User $user
     * @return Task
     */
    public function completeWithoutApproval(Task $task, User $user): Task;

    /**
     * タスクを通知なしで承認する。
     * @param Task $task
     * @param User $approver
     * @return Task
     */
    public function approveTaskWithoutNotification(Task $task, User $approver): Task;
}