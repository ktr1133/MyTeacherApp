<?php

namespace App\Repositories\Tag;

use App\Models\User;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;


interface TagRepositoryInterface
{
    /**
     * 指定されたタグ名のID配列を取得する。
     *
     * 存在しないタグ名があれば新規作成し、そのIDも含める。
     *
     * @param array<string> $names タグ名の配列
     * @return array<int> タグIDの配列
     */
    public function getIdsForNames(array $names): array;

    /**
     * 指定されたユーザーIDに紐づくタグを取得する。
     *
     * @param int $userId
     * @return array<Tag>
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
     * ユーザーのタグ一覧をタスク件数付きで取得
     *
     * @param int $userId
     * @return Collection<Tag>
     */
    public function getByUserIdWithTaskCount(int $userId): Collection;

    /**
     * タグに紐づくタスク一覧を取得
     *
     * @param Tag $tag
     * @return Collection<Task>
     */
    public function getLinkedTasks(Tag $tag): Collection;

    /**
     * タグに紐づいていないユーザーのタスク一覧を取得
     *
     * @param Tag $tag
     * @param int $userId
     * @param int $limit
     * @return Collection<Task>
     */
    public function getAvailableTasks(Tag $tag, int $userId, int $limit = 200): Collection;

    /**
     * タスクをタグに紐付ける
     *
     * @param Tag $tag
     * @param int $taskId
     * @return void
     */
    public function attachTask(Tag $tag, int $taskId): void;

    /**
     * タスクからタグを解除
     *
     * @param Tag $tag
     * @param int $taskId
     * @return void
     */
    public function detachTask(Tag $tag, int $taskId): void;
}