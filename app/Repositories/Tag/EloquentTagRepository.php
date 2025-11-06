<?php

namespace App\Repositories\Tag;

use App\Models\User;
use App\Models\Tag;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

class EloquentTagRepository implements TagRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function getIdsForNames(array $names): array
    {
        $ids = [];
        foreach ($names as $name) {
            $ids[] = Tag::firstOrCreate(['name' => $name])->id;
        }
        return $ids;
    }

    /**
     * @inheritDoc
     */
    public function createTag(User $user, array $data): Tag
    {
        $data['user_id'] = $user->id;

        return Tag::create($data);
    }

    /**
     * @inheritDoc
     */
    public function updateTag(User $user, array $data): Tag
    {
        $tag = Tag::findOrFail($data['id']);

        if ($tag->user_id !== $user->id) {
            throw new \Exception('更新対象のタグを取得できませんでした。');
        }

        $tag->update($data);

        return $tag;
    }

    /**
     * @inheritDoc
     */
    public function deleteTag(int $id): bool
    {
        $tag = Tag::find($id);
        if (!$tag) {
            return false;
        }

        return $tag->delete();
    }


    public function getTasksByUserId(int $userId): array
    {
        $tags = Tag::where('user_id', $userId)->get();

        $tasks = [];
        foreach ($tags as $tag) {
            foreach ($tag->tasks as $task) {
                $tasks[$task->id] = $task;
            }
        }

        return array_values($tasks);
    }

    /**
     * @inheritDoc
     */
    public function getByUserIdWithTaskCount(int $userId): Collection
    {
        return Tag::where('user_id', $userId)
            ->withCount('tasks')
            ->orderBy('name')
            ->get();
    }

    /**
     * @inheritDoc
     */
    public function getLinkedTasks(Tag $tag): Collection
    {
        return $tag->tasks()
            ->select('tasks.id', 'tasks.title')
            ->orderBy('tasks.created_at', 'desc')
            ->get();
    }

    /**
     * @inheritDoc
     */
    public function getAvailableTasks(Tag $tag, int $userId, int $limit = 200): Collection
    {
        return Task::query()
            ->where('user_id', $userId)
            ->whereDoesntHave('tags', fn($q) => $q->where('tags.id', $tag->id))
            ->select('id', 'title')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * @inheritDoc
     */
    public function attachTask(Tag $tag, int $taskId): void
    {
        $tag->tasks()->syncWithoutDetaching([$taskId]);
    }

    /**
     * @inheritDoc
     */
    public function detachTask(Tag $tag, int $taskId): void
    {
        $tag->tasks()->detach($taskId);
    }
}