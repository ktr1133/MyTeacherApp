<?php

namespace App\Repositories\Task;

use App\Models\Task;
use App\Models\Tag;
use App\Models\TaskProposal; // task_proposalsテーブル用
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * TaskRepositoryInterface および TaskManagementService で使用されるデータアクセス操作を
 * Eloquent ORMで実装する具象クラス。
 */
class TaskEloquentRepository implements TaskRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function create(array $data): Task
    {
        // 必要に応じて fillable を Task モデルに設定してください
        return Task::create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data) {
            // タグは別処理に回す
            $tags = null;
            if (array_key_exists('tags', $data)) {
                $tags = $data['tags'];
                unset($data['tags']);
            }

            // fillable に沿って安全に更新
            $fillable = $task->getFillable();
            if (!empty($fillable)) {
                $data = array_intersect_key($data, array_flip($fillable));
            }

            $task->fill($data);
            $task->save();

            // タグ同期（キーが存在した場合のみ）
            if ($tags !== null) {
                if (is_array($tags) && count($tags) > 0) {
                    $this->syncTagsByName($task, $tags);
                } else {
                    $task->tags()->detach();
                }
            }

            return $task->fresh(['tags']);
        });
    }

    /**
     * @inheritDoc
     */
    public function getTasksByUserId(int $userId): Builder
    {
        return Task::query()
            ->select([
                'id', 'user_id', 'title', 'description',
                'due_date', 'span', 'priority', 'is_completed',
                'completed_at', 'group_task_id', 'reward',
                'requires_approval', 'requires_image', 'approved_at',
                'assigned_by_user_id', 'approved_by_user_id',
                'source_proposal_id', 'created_at', 'updated_at', 'deleted_at'
            ])
            ->with(['tags', 'images'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at');
    }

    /**
     * @inheritDoc
     */
    public function getAllTags(): Collection
    {
        $user = Auth::user();
        if (!$user) {
            abort(404, 'ユーザーが認証されていません。');
        }

        return Tag::where('user_id', $user->id)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * @inheritDoc
     */
    public function createTask(int $userId, array $data): Task
    {
        // Taskモデルのfillableを取得
        $fillable = (new Task())->getFillable();

        // fillableに含まれるカラムのみを抽出
        $attributes = array_intersect_key($data, array_flip($fillable));

        // 必須項目設定
        $attributes['user_id'] = $userId;
        $attributes['priority'] = $data['priority'] ?? 3;
        $attributes['requires_approval'] = $data['requires_approval'] ?? false;

        return Task::create($attributes);
    }

    /**
     * @inheritDoc
     */
    public function syncTagsByName(Task $task, array $tagNames): void
    {
        if (empty($tagNames)) {
            $task->tags()->detach();
            return;
        }

        // 1. 既存のタグIDと、新規作成が必要なタグ名を分離する（user_idでフィルタリング）
        $existingTags = Tag::where('user_id', $task->user_id)
            ->whereIn('name', $tagNames)
            ->pluck('id', 'name')
            ->toArray();
        $existingTagNames = array_keys($existingTags);
        $newTagNames = array_diff($tagNames, $existingTagNames);

        // 2. 新規タグを作成（user_idを含める）
        $now = now();
        $newTags = [];
        foreach ($newTagNames as $name) {
            $newTags[] = [
                'name' => $name,
                'user_id' => $task->user_id, // タスクのuser_idを使用
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        if (!empty($newTags)) {
            Tag::insert($newTags);
        }
        
        // 3. 全てのタグIDを取得し直す (新規作成されたIDを含む、user_idでフィルタリング)
        $allTagIds = Tag::where('user_id', $task->user_id)
            ->whereIn('name', $tagNames)
            ->pluck('id')
            ->toArray();
        
        // 4. タスクにタグを関連付け/同期 (Many-to-Many)
        $task->tags()->sync($allTagIds);
    }
    
    /**
     * @inheritDoc
     */
    public function createProposal(int $userId, string $originalTaskText, string $context, array $aiResponse): TaskProposal
    {
        return TaskProposal::create([
            'user_id' => $userId,
            'original_task_text' => $originalTaskText,
            'proposal_context' => $context,
            'proposed_tasks_json' => json_encode($aiResponse['tasks']), // AIレスポンスからタスクリストを抽出してJSONとして保存
            'model_used' => $aiResponse['model_used'] ?? 'OpenAI_GPT-4', 
            'was_adopted' => false,
        ]);
    }
    
    /**
     * @inheritDoc
     */
    public function createTasksFromProposal(int $userId, int $proposalId, array $proposedTasks): SupportCollection
    {
        $createdTasks = collect();

        DB::transaction(function () use ($userId, $proposalId, $proposedTasks, &$createdTasks) {
            foreach ($proposedTasks as $taskData) {
                // タグ、期限、優先度などのデータはAIレスポンスの構造に依存
                $task = Task::create([
                    'user_id' => $userId,
                    'title' => $taskData['title'],
                    'description' => $taskData['description'] ?? null,
                    'due_date' => $taskData['due_date'] ?? null,
                    'priority' => $taskData['priority'] ?? 3,
                    'source_proposal_id' => $proposalId, // 提案IDを記録
                ]);
                
                // タグがある場合は関連付け (AI提案ではタグ名を使用すると仮定)
                if (!empty($taskData['tags'])) {
                    // ここでタグ同期ロジックを再利用可能
                    $this->syncTagsByName($task, (array)$taskData['tags']);
                }
                
                $createdTasks->push($task);
            }
            
            // 提案全体が採用されたことを記録
            TaskProposal::where('id', $proposalId)->update(['was_adopted' => true]);
        });

        return $createdTasks;
    }

    /**
     * タイトルでタスクを検索
     *
     * @param int $userId
     * @param array $terms
     * @param string $operator 'and' or 'or'
     * @return Collection
     */
    public function searchByTitle(int $userId, array $terms, string $operator): Collection
    {
        $query = Task::where('user_id', $userId);

        $query->where(function ($q) use ($terms, $operator) {
            if ($operator === 'and') {
                foreach ($terms as $term) {
                    $q->where('title', 'LIKE', "%{$term}%");
                }
            } else {
                foreach ($terms as $term) {
                    $q->orWhere('title', 'LIKE', "%{$term}%");
                }
            }
        });

        return $query->with('tags')
            ->orderBy('due_date', 'asc')
            ->limit(50)
            ->get();
    }

    /**
     * タグでタスクを検索
     *
     * @param int $userId
     * @param array $terms
     * @param string $operator 'and' or 'or'
     * @return Collection
     */
    public function searchByTags(int $userId, array $terms, string $operator): Collection
    {
        $query = Task::where('user_id', $userId);

        $query->whereHas('tags', function ($q) use ($terms, $operator) {
            if ($operator === 'and') {
                foreach ($terms as $term) {
                    $q->where('name', 'LIKE', "%{$term}%");
                }
            } else {
                $q->where(function ($subQ) use ($terms) {
                    foreach ($terms as $term) {
                        $subQ->orWhere('name', 'LIKE', "%{$term}%");
                    }
                });
            }
        });

        return $query->with('tags')
            ->orderBy('due_date', 'asc')
            ->limit(50)
            ->get();
    }

    /**
     * @inheritDoc
     */
    public function deleteTask(Task $task): bool
    {
        return DB::transaction(function () use ($task) {
            // 1. タグとのリレーションを削除
            $this->detachAllTags($task);

            // 2. タスク本体をソフトデリート
            return $task->delete();
        });
    }

    /**
     * @inheritDoc
     */
    public function detachAllTags(Task $task): void
    {
        $task->tags()->detach();
    }

    /**
     * @inheritDoc
     */
    public function softDeleteTask(Task $task): bool
    {
        return $task->delete(); // Laravelのソフトデリート
    }

    /**
     * @inheritDoc
     */
    public function restoreTask(Task $task): bool
    {
        return $task->restore();
    }

    /**
     * @inheritDoc
     */
    public function findTaskById(int $taskId): ?Task
    {
        return Task::where('id', $taskId)
            ->withTrashed()
            ->with('tags')
            ->first();
    }

    /**
     * @inheritDoc
     */
    public function deleteByGroupTaskIdExcludingUser(string $groupTaskId, int $userId): int
    {
        return Task::where('group_task_id', $groupTaskId)
            ->where('user_id', '!=', $userId)
            ->delete();
    }

    /**
     * @inheritDoc
     */
    public function restoreByGroupTaskId(string $groupTaskId): int
    {
        return Task::withTrashed()
            ->where('group_task_id', $groupTaskId)
            ->restore();
    }

    /**
     * タスクにタグを紐付け（Batch用）
     */
    public function attachTagsForBatch(int $taskId, array $tagNames): void
    {
        if (empty($tagNames)) {
            return;
        }

        // タスクを取得
        $task = Task::find($taskId);
        if (!$task) {
            return;
        }

        $tagIds = [];

        foreach ($tagNames as $tagName) {
            // タグを取得または作成（user_idはタスクの所有者）
            $tag = Tag::firstOrCreate(
                [
                    'name' => $tagName,
                    'user_id' => $task->user_id
                ]
            );
            $tagIds[] = $tag->id;
        }

        // タグIDを紐付け（重複を避けるためsyncWithoutDetachingを使用）
        if (!empty($tagIds)) {
            $task->tags()->syncWithoutDetaching($tagIds);
        }
    }

    /**
     * グループのメンバーIDを取得（Batch用）
     */
    public function getGroupMemberIds(int $groupId): array
    {
        return DB::table('users')
            ->where('group_id', $groupId)
            ->where('group_edit_flg', false) // 編集権限なし = タスク受取側
            ->pluck('id')
            ->toArray();
    }

    /**
     * タスクを論理削除（Batch用）
     */
    public function softDeleteById(int $taskId): bool
    {
        $task = Task::find($taskId);
        
        if (!$task || $task->trashed()) {
            return false;
        }

        return $task->delete();
    }

    /**
     * 指定されたgroup_task_idを持つすべてのタスクを取得
     */
    public function findTasksByGroupTaskId(string $groupTaskId): Collection
    {
        return Task::where('group_task_id', $groupTaskId)
            ->with(['user', 'tags'])
            ->get();
    }

    /**
     * ユーザーが作成した編集可能なグループタスクを取得（group_task_id単位でグループ化）
     */
    public function findEditableGroupTasksByUser(int $userId): \Illuminate\Support\Collection
    {
        return Task::whereNotNull('group_task_id')
            ->where('assigned_by_user_id', $userId)
            ->whereNull('approved_at')
            ->with(['user', 'tags', 'assignedBy'])
            ->get()
            ->groupBy('group_task_id')
            ->map(function ($tasks) {
                // 各グループの代表タスク（最初のタスク）を取得
                $firstTask = $tasks->first();
                
                return [
                    'group_task_id' => $firstTask->group_task_id,
                    'title' => $firstTask->title,
                    'description' => $firstTask->description,
                    'span' => $firstTask->span,
                    'due_date' => $firstTask->due_date,
                    'priority' => $firstTask->priority,
                    'reward' => $firstTask->reward,
                    'requires_approval' => $firstTask->requires_approval,
                    'requires_image' => $firstTask->requires_image,
                    'assigned_count' => $tasks->count(),
                    'tags' => $firstTask->tags,
                    'created_at' => $firstTask->created_at,
                    'updated_at' => $firstTask->updated_at,
                    'tasks' => $tasks, // 全タスクも含める
                ];
            })
            ->values();
    }

    /**
     * 特定のgroup_task_idのタスクを取得（編集可能なもののみ）
     */
    public function findEditableTasksByGroupTaskId(string $groupTaskId, int $assignedByUserId): Collection
    {
        return Task::where('group_task_id', $groupTaskId)
            ->where('assigned_by_user_id', $assignedByUserId)
            ->whereNull('approved_at')
            ->with(['user', 'tags', 'assignedBy'])
            ->get();
    }

    /**
     * グループタスクを一括更新（同じgroup_task_idのタスク全体）
     */
    public function updateTasksByGroupTaskId(string $groupTaskId, int $assignedByUserId, array $data): int
    {
        // タグは除外（別処理）
        if (isset($data['tags'])) {
            unset($data['tags']);
        }

        return Task::where('group_task_id', $groupTaskId)
            ->where('assigned_by_user_id', $assignedByUserId)
            ->whereNull('approved_at')
            ->update($data);
    }

    /**
     * グループタスクを一括論理削除（同じgroup_task_idのタスク全体）
     */
    public function softDeleteTasksByGroupTaskId(string $groupTaskId, int $assignedByUserId): int
    {
        $tasks = Task::where('group_task_id', $groupTaskId)
            ->where('assigned_by_user_id', $assignedByUserId)
            ->whereNull('approved_at')
            ->get();

        $count = 0;
        foreach ($tasks as $task) {
            if ($task->delete()) {
                $count++;
            }
        }

        return $count;
    }
}