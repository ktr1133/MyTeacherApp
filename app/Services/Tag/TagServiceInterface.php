<?php

namespace App\Services\Tag;

use App\Models\User;
use App\Models\Tag;
use App\Models\Task;

interface TagServiceInterface
{
    /**
     * タスクに名前で指定されたタグを同期する。
     *
     * @param Task $task
     * @param array<string> $names
     * @return void
     */
    public function syncTaskTagsByNames(Task $task, array $names): void;

    /**
     * タグを検索または作成
     * 
     * @param User $user
     * @param string $name
     * @return Tag
     */
    public function findOrCreate(User $user, string $name): Tag;

    /**
     * 指定されたユーザーIDに紐づくタグを取得する。
     *
     * @param int $userId
     * @return array<Tag>
     */
    public function getByUserId(int $userId): array;

    /**
     * タグをDBに保存する。
     *
     * @param User $user
     * @param array $data
     * @return Tag
     */
    public function createTag(User $user, array $data): Tag;

    /**
     * タグを更新する。
     *
     * @param User $user
     * @param array $data
     * @return Tag
     */
    public function updateTag(User $user, array $data): Tag;

    /**
     * タグを削除する。
     *
     * @param int $id
     * @return bool
     */
    public function deleteTag(int $id): bool;

    /**
     * 指定されたユーザーIDに紐づくタグに関連付けられたタスクを取得する。
     *
     * @param int $userId
     * @return array<Task>
     */
    public function getTasksByUserId(int $userId): array;

    /**
     * タグに紐づくタスクと未紐付けタスクを取得
     *
     * @param Tag $tag
     * @param int $userId
     * @return array{linked: array, available: array}
     */
    public function getTagTasks(Tag $tag, int $userId): array;

    /**
     * タスクをタグに紐付ける
     *
     * @param Tag $tag
     * @param int $taskId
     * @param int $userId
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function attachTaskToTag(Tag $tag, int $taskId, int $userId): void;

    /**
     * タスクからタグを解除
     *
     * @param Tag $tag
     * @param int $taskId
     * @param int $userId
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function detachTaskFromTag(Tag $tag, int $taskId, int $userId): void;

    /**
     * タグの所有者を確認
     *
     * @param Tag $tag
     * @param int $userId
     * @return bool
     */
    public function isOwner(Tag $tag, int $userId): bool;
}