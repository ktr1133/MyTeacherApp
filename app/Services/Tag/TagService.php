<?php

namespace App\Services\Tag;

use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
        $tag = Tag::firstOrCreate([
            'user_id' => $user->id,
            'name' => $name,
        ]);
        
        // キャッシュクリア
        Cache::forget("user:{$user->id}:tags");
        
        return $tag;
    }

    /**
     * ユーザーIDでタグを取得（キャッシュ付き）
     *
     * @param int $userId ユーザーID
     * @return array タグの配列
     */
    public function getByUserId(int $userId): array
    {
        try {
            return Cache::remember(
                "user:{$userId}:tags",
                now()->addHours(6),
                fn() => Tag::where('user_id', $userId)->get()->all()
            );
        } catch (\Exception $e) {
            Log::warning('Cache unavailable for tags, using database', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return Tag::where('user_id', $userId)->get()->all();
        }
    }

    /**
     * タグを作成
     *
     * @param User $user ユーザー
     * @param array $data タグデータ
     * @return Tag 作成されたタグ
     */
    public function createTag(User $user, array $data): Tag
    {
        $tag = $this->tags->createTag($user, $data);
        Cache::forget("user:{$user->id}:tags");

        return $tag;
    }

    /**
     * タグを更新
     *
     * @param User $user ユーザー
     * @param array $data タグデータ
     * @return Tag 更新されたタグ
     */
    public function updateTag(User $user, array $data): Tag
    {
        $tag = $this->tags->updateTag($user, $data);
        Cache::forget("user:{$user->id}:tags");

        return $tag;
    }

    /**
     * タグを削除
     *
     * @param int $id タグID
     * @return bool 削除成功の場合true
     * @throws \Illuminate\Auth\Access\AuthorizationException 権限がない場合
     */
    public function deleteTag(int $id): bool
    {
        // 付属するタスクがあれば削除できないようにする
        $tag = $this->tags->findById($id);
        if (!$tag) {
            return false;
        }
        
        // 権限チェック: ログインユーザーとタグの所有者が一致するか
        $currentUser = auth()->user();
        if ($currentUser && $tag->user_id !== $currentUser->id) {
            throw new \Illuminate\Auth\Access\AuthorizationException('他のユーザーのタグは削除できません。');
        }
        
        if ($tag->tasks()->count() > 0) {
            return false;
        }
        
        $result = $this->tags->deleteTag($id);
        
        if ($result) {
            Cache::forget("user:{$tag->user_id}:tags");
        }
        
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getTasksByUserId(int $userId): array
    {
        return $this->tags->getTasksByUserId($userId);
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