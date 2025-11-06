<?php

namespace App\Services\Task;

use App\Models\Task;
use App\Models\User;
use App\Repositories\Profile\ProfileUserRepositoryInterface;
use App\Repositories\Task\TaskRepositoryInterface;
use App\Services\AI\OpenAIService;
use App\Services\Tag\TagServiceInterface;
use App\Services\Task\TaskProposalServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;


/**
 * TaskManagementServiceInterface の具象クラス。
 */
class TaskManagementService implements TaskManagementServiceInterface
{
    protected TaskRepositoryInterface $taskRepository;
    protected ProfileUserRepositoryInterface $profileUserRepository;
    protected OpenAIService $openAIService;
    protected TagServiceInterface $tagService;
    protected TaskProposalServiceInterface $proposalService;

    /**
     * コンストラクタ。リポジトリとAIサービスの依存性を注入。
     */
    public function __construct(
        TaskRepositoryInterface $taskRepository,
        ProfileUserRepositoryInterface $profileUserRepository,
        OpenAIService $openAIService,
        TagServiceInterface $tagService,
        TaskProposalServiceInterface $proposalService
    ){
        $this->taskRepository = $taskRepository;
        $this->profileUserRepository = $profileUserRepository;
        $this->openAIService = $openAIService;
        $this->tagService = $tagService;
        $this->proposalService = $proposalService;
    }

    /**
     * @inheritDoc
     */
    public function findById(int $id): ?Task
    {
        return $this->taskRepository->findTaskById($id);
    }

    /**
     * @inheritDoc
     */
    public function createTask(User $user, array $data, bool $groupFlg): Task
    {
        // タスク登録用データの作成
        $taskData = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'span' => $data['span'],
            'due_date' => $data['due_date'] ?? null,
            'priority' => $data['priority'] ?? 3,
            'is_completed' => false,
        ];

        DB::transaction(function () use ($user, $data, $taskData, $groupFlg, &$task) {
            // グループタスクの場合
            if ($groupFlg) {
                // 追加フィールドの設定
                $taskData['reward'] = $data['reward'];
                $taskData['requires_approval'] = $data['requires_approval'];
                $taskData['assigned_by_user_id'] = Auth::user()->id;
                $taskData['group_task_id'] = $data['group_task_id'];
                // 担当者が未設定の場合はグループの編集権限のないメンバー全員分にタスクを作成する
                if (is_null($data['user_id'])) {
                    // グループメンバのうち、編集権限のないユーザを取得
                    $users = $this->profileUserRepository->getMembersWithoutEditPermission($user->id);
                    foreach ($users as $user) {
                        $taskData['user_id'] = $user->id;
                        $task = $this->taskRepository->createTask($user->id, $taskData);

                        // タグを関連付け（タグ名の配列）
                        if (isset($data['tags']) && is_array($data['tags'])) {
                            $this->taskRepository->syncTagsByName($task, $data['tags']);
                        }            
                    }
                // 担当者が設定されている場合は担当者分のみタスクを作成
                } else {
                    $taskData['user_id'] = $data['assigned_by_user_id'];
                    $task = $this->taskRepository->createTask($user->id, $taskData);
                    // タグを関連付け（タグ名の配列）
                    if (isset($data['tags']) && is_array($data['tags'])) {
                        $this->taskRepository->syncTagsByName($task, $data['tags']);
                    }            
                }
            // 通常タスク登録の場合
            } else {
                $task = $this->taskRepository->createTask($user->id, $taskData);
                
                // タグを関連付け（タグ名の配列）
                if (isset($data['tags']) && is_array($data['tags'])) {
                    $this->taskRepository->syncTagsByName($task, $data['tags']);
                }            
            }
        });
    
        return $task->fresh(['tags']);
    }

    /**
     * @inheritDoc
     */
    public function updateTask(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data) {
            // 基本情報を更新
            $updateData = [
                'title' => $data['title'] ?? $task->title,
                'description' => $data['description'] ?? $task->description,
                'span' => $data['span'] ?? $task->span,
                'due_date' => $data['due_date'] ?? $task->due_date,
            ];

            $task->update($updateData);

            // タグの同期（多対多リレーション）
            if (array_key_exists('tags', $data)) {
                if (!empty($data['tags'])) {
                    $task->tags()->sync($data['tags']);
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
    public function deleteTask(Task $task): bool
    {
        try {
            // リポジトリを使用してタスクを削除
            $deleted = $this->taskRepository->deleteTask($task);

            if (!$deleted) {
                Log::warning('Task deletion returned false', ['task_id' => $task->id]);
            }

            return $deleted;
        } catch (\Exception $e) {
            Log::error('Task deletion failed', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function proposeTaskDecomposition(User $user, string $originalTaskText, string $context): array
    {
        // 1. AIサービスに分解を依頼
        $aiResponse = $this->openAIService->requestDecomposition($originalTaskText, $context);
        
        // 2. 提案結果をDBに記録
        $proposal = $this->taskRepository->createProposal($user->id, $originalTaskText, $context, $aiResponse);
        
        return [
            'proposal' => $proposal,
            'proposed_tasks' => json_decode($proposal->proposed_tasks_json, true),
        ];
    }
    
    /**
     * @inheritDoc
     */
    public function registerProposedTasks(User $user, int $proposalId, array $proposedTasks): \Illuminate\Support\Collection
    {
        // 提案IDを付けてタスクを一括登録するロジック
        return $this->taskRepository->createTasksFromProposal($user->id, $proposalId, $proposedTasks);
    }

    /**
     * @inheritDoc
     */
    public function adoptProposal(User $user, string|int $proposalId, array $tasks): array
    {
        return DB::transaction(function () use ($user, $proposalId, $tasks) {
            $createdTasks = [];

            foreach ($tasks as $taskData) {
                // タスクを作成
                $task = $this->taskRepository->create([
                    'user_id' => $user->id,
                    'title' => $taskData['title'],
                    'span' => (int)$taskData['span'],
                    'priority' => $taskData['priority'] ?? 3,
                    'source_proposal_id' => $proposalId,
                    'is_completed' => false,
                ]);

                // タグの処理（タグ名の配列）
                if (!empty($taskData['tags']) && is_array($taskData['tags'])) {
                    $this->taskRepository->syncTagsByName($task, $taskData['tags']);
                }

                // タスクをリロードしてリレーションを含める
                $task->load('tags');
                $createdTasks[] = $task;
            }

            // 提案を採用済みとしてマーク
            $this->proposalService->markAsAdopted(
                $proposalId,
                array_map(fn($t) => $t->id, $createdTasks)
            );

            return $createdTasks;
        });
    }

    /**
     * @inheritDoc
     */
    public function getUserById(int $userId): User
    {
        return $this->profileUserRepository->findById($userId);
    }
}