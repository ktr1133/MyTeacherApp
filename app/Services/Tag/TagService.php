<?php

namespace App\Services\Tag;

use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Tag\TagRepositoryInterface;

class TagService implements TagServiceInterface
{
    /**
     * コンストラクタでTagRepositoryを注入
     */
    public function __construct(
        private TagRepositoryInterface $tags
    ) {}

    /**
     * @inheritDoc
     */
    public function syncTaskTagsByNames(Task $task, array $names): void
    {
        $names = array_values(array_filter(array_map('trim', $names), fn($n) => $n !== ''));
        if (!$names) return;

        $ids = $this->tags->getIdsForNames($names);
        $task->tags()->syncWithoutDetaching($ids);
    }

    /**
     * @inheritDoc
     */
    public function findOrCreate(User $user, string $name): Tag
    {
        return Tag::firstOrCreate([
            'user_id' => $user->id,
            'name' => $name,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getByUserId(int $userId): array
    {
        return Tag::where('user_id', $userId)->get()->all();
    }

    /**
     * @inheritDoc
     */
    public function createTag(User $user, array $data): Tag
    {
        return $this->tags->createTag($user, $data);
    }

    /**
     * @inheritDoc
     */
    public function updateTag(User $user, array $data): Tag
    {
        return $this->tags->updateTag($user, $data);
    }

    /**
     * @inheritDoc
     */
    public function deleteTag(int $id): bool
    {
        return $this->tags->deleteTag($id);
    }

    /**
     * @inheritDoc
     */
    public function getTasksByUserId(int $userId): array
    {
        return $this->tags->getByUserIdWithTaskCount($userId)->all();
    }

    /**
     * @inheritDoc
     */
    public function getTagTasks(Tag $tag, int $userId): array
    {
        $linked = $this->tags->getLinkedTasks($tag)->map(function ($task) {
            return [
                'id'    => $task->id,
                'title' => $task->title,
            ];
        })->toArray();

        $available = $this->tags->getAvailableTasks($tag, $userId)->map(function ($task) {
            return [
                'id'    => $task->id,
                'title' => $task->title,
            ];
        })->toArray();

        return [
            'linked'    => $linked,
            'available' => $available,
        ];
    }

    /**
     * @inheritDoc
     */
    public function attachTaskToTag(Tag $tag, int $taskId, int $userId): void
    {
        // タスクの所有者確認
        $task = Task::where('id', $taskId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $this->tags->attachTask($tag, $taskId);
    }

    /**
     * @inheritDoc
     */
    public function detachTaskFromTag(Tag $tag, int $taskId, int $userId): void
    {
        // タスクの所有者確認
        $task = Task::where('id', $taskId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $this->tags->detachTask($tag, $taskId);
    }

    /**
     * @inheritDoc
     */
    public function isOwner(Tag $tag, int $userId): bool
    {
        return $tag->user_id === $userId;
    }
}