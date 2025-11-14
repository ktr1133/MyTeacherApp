<?php

namespace App\Repositories\Task;

use App\Models\Task;
use App\Models\Tag;
use App\Models\TaskProposal; // task_proposalsテーブル用
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
            ->with(['tags', 'images'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at');
    }

    /**
     * @inheritDoc
     */
    public function getAllTags(): Collection
    {
        /**
         * データベースに登録されている全てのタグを取得する。
         */
        return Tag::all();
    }

    /**
     * @inheritDoc
     */
    public function createTask(int $userId, array $data): Task
    {
        return Task::create([
            'user_id'              => $userId,
            'title'                => $data['title'],
            'description'          => $data['description'] ?? null,
            'span'                 => (int) $data['span'] ?? null,
            'due_date'             => $data['due_date'] ?? null,
            'priority'             => $data['priority'] ?? 3,
            'reward'               => $data['reward'] ?? null,
            'group_task_id'        => $data['group_task_id'] ?? null,
            'source_proposal_id'   => $data['source_proposal_id'] ?? null,
            'requires_approval'    => $data['requires_approval'] ?? false,
            'assigned_by_user_id'  => $data['assigned_by_user_id'] ?? null,
        ]);
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

        // 1. 既存のタグIDと、新規作成が必要なタグ名を分離する
        $existingTags = Tag::whereIn('name', $tagNames)->pluck('id', 'name')->toArray();
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
        
        // 3. 全てのタグIDを取得し直す (新規作成されたIDを含む)
        $allTagIds = Tag::whereIn('name', $tagNames)->pluck('id')->toArray();
        
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
    public function createTasksFromProposal(int $userId, int $proposalId, array $proposedTasks): Collection
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
     * @return array
     */
    public function searchByTitle(int $userId, array $terms, string $operator): array
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
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'span' => $task->span,
                    'due_date' => $task->due_date,
                    'tags' => $task->tags->pluck('name')->toArray(),
                ];
            })
            ->toArray();
    }

    /**
     * タグでタスクを検索
     *
     * @param int $userId
     * @param array $terms
     * @param string $operator 'and' or 'or'
     * @return array
     */
    public function searchByTags(int $userId, array $terms, string $operator): array
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
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'span' => $task->span,
                    'due_date' => $task->due_date,
                    'tags' => $task->tags->pluck('name')->toArray(),
                ];
            })
            ->toArray();
    }

    /**
     * @inheritDoc
     */
    public function deleteTask(Task $task): bool
    {
        return DB::transaction(function () use ($task) {
            // 1. タグとのリレーションを削除
            $this->detachAllTags($task);

            // 2. タスク本体を削除（物理削除）
            return $task->forceDelete();
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

        $now = now();
        $insertData = [];

        foreach ($tagNames as $tagName) {
            $insertData[] = [
                'task_id' => $taskId,
                'tag_name' => $tagName,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('task_tags')->insert($insertData);
    }

    /**
     * グループのメンバーIDを取得（Batch用）
     */
    public function getGroupMemberIds(int $groupId): array
    {
        return DB::table('group_user')
            ->where('group_id', $groupId)
            ->pluck('user_id')
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
}