<?php

namespace App\Services\Task;

use App\Models\Task;
use App\Models\User;
use App\Repositories\Profile\ProfileUserRepositoryInterface;
use App\Repositories\Tag\TagRepositoryInterface;
use App\Repositories\Task\TaskRepositoryInterface;
use App\Services\AI\OpenAIService;
use App\Services\Notification\NotificationServiceInterface;
use App\Services\Tag\TagServiceInterface;
use App\Services\Task\TaskProposalServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;


/**
 * TaskManagementServiceInterface の具象クラス。
 */
class TaskManagementService implements TaskManagementServiceInterface
{
    protected TaskRepositoryInterface $taskRepository;
    protected TagRepositoryInterface $tagRepository;
    protected ProfileUserRepositoryInterface $profileUserRepository;
    protected OpenAIService $openAIService;
    protected TagServiceInterface $tagService;
    protected TaskProposalServiceInterface $proposalService;
    protected NotificationServiceInterface $notificationService;

    /**
     * コンストラクタ。リポジトリとAIサービスの依存性を注入。
     */
    public function __construct(
        TaskRepositoryInterface $taskRepository,
        TagRepositoryInterface $tagRepository,
        ProfileUserRepositoryInterface $profileUserRepository,
        OpenAIService $openAIService,
        TagServiceInterface $tagService,
        TaskProposalServiceInterface $proposalService,
        NotificationServiceInterface $notificationService
    ){
        $this->taskRepository = $taskRepository;
        $this->tagRepository = $tagRepository;
        $this->profileUserRepository = $profileUserRepository;
        $this->openAIService = $openAIService;
        $this->tagService = $tagService;
        $this->proposalService = $proposalService;
        $this->notificationService = $notificationService;
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
        $taskData = $this->makeTaskBaseData($data);
        $taskData['priority'] = $data['priority'] ?? 3;
        $taskData['is_completed'] = false;
        $is_charged = isset($data['assigned_user_id']) ? true : false;

        DB::transaction(function () use ($user, $data, $taskData, $groupFlg, $is_charged, &$task) {
            // グループタスクの場合
            if ($groupFlg) {
                // 追加フィールドの設定
                $taskData['reward'] = $data['reward'];
                $taskData['requires_approval'] = $data['requires_approval'];
                $taskData['assigned_by_user_id'] = Auth::user()->id;
                $taskData['group_task_id'] = $data['group_task_id'];
                // 担当者が未設定の場合はグループの編集権限のないメンバー全員分にタスクを作成する
                if (!$is_charged) {
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
                    // 通知を送信
                    $this->notificationService->sendNotificationForGroup(
                        config('const.notification_types.group_task_created'),
                        '新しいグループタスクが作成されました。',
                        '新しいグループタスク: ' . $data['title'] . 'が作成されました。タスクリストを確認してください。',
                        'important'
                    );
                // 担当者が設定されている場合は担当者分のみタスクを作成
                } else {
                    $taskData['user_id'] = $data['assigned_user_id'];
                    $task = $this->taskRepository->createTask($user->id, $taskData);
                    // タグを関連付け（タグ名の配列）
                    if (isset($data['tags']) && is_array($data['tags'])) {
                        $this->taskRepository->syncTagsByName($task, $data['tags']);
                    }
                    // 担当者に通知を送信
                    $this->notificationService->sendNotification(
                        Auth::user()->id,
                        $data['assigned_user_id'],
                        config('const.notification_types.group_task_created'),
                        '新しいグループタスクが作成されました。',
                        '新しいグループタスク: ' . $data['title'] . 'が作成されました。タスクリストを確認してください。',
                        'important'
                    );       
                }
            // 通常タスク登録の場合
            } else {
                $task = $this->taskRepository->createTask($user->id, $taskData);
                // タグを関連付け（タグ名の配列）
                if (isset($data['tags']) && is_array($data['tags'])) {
                    $tagNames = $this->tagRepository->findByIds($data['tags'])->pluck('name')->toArray();
                    $this->taskRepository->syncTagsByName($task, $tagNames);
                }            
            }
        });
    
        return $task->fresh(['tags']);
    }

    /**
     * タスク登録用の基本データを作成するヘルパーメソッド
     *
     * @param array $data 入力データ
     * @return array タスク登録用データ
     */
    public function makeTaskBaseData(array $data): array
    {
        if ($data['span'] == config('const.task_spans.mid')) {
            try {
                // 更新の場合は既存のdue_dateをパース
                $data['due_date'] = Carbon::parse($data['due_date'])->format('Y-m-d');
            } catch (\Exception $e) {
                // 新規の場合はdue_dataは年末に設定
                $tmp_due_data = $data['due_date'];
                $data['due_date'] = Carbon::createFromFormat('Y', $tmp_due_data)->endOfYear()->format('Y-m-d');
            }
        }

        return [
            'title'        => $data['title'],
            'description'  => $data['description'] ?? null,
            'span'         => $data['span'],
            'due_date'     => $data['due_date'] ?? null,
        ];
    } 

    /**
     * @inheritDoc
     */
    public function updateTask(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data) {
            // 基本情報を更新
            $updateData = $this->makeTaskBaseData($data);

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

            foreach ($tasks as $data) {
                $taskData = $this->makeTaskBaseData($data);
                $taskData['priority'] = $data['priority'] ?? 3;
                $taskData['user_id'] = $user->id;
                $taskData['is_completed'] = false;
                $taskData['source_proposal_id'] = $proposalId;
                // タスクを作成
                logger()->info('タスク受け入れサービス', ['taskData' => $taskData, 'tasks' => $tasks]);
                $task = $this->taskRepository->create($taskData);

                // タグの処理（タグ名の配列）
                if (!empty($data['tags']) && is_array($data['tags'])) {
                    $this->taskRepository->syncTagsByName($task, $data['tags']);
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

    /**
     * @inheritDoc
     */
    public function updateTaskDescription(Task $task, ?string $description, int $userId): Task
    {
        // 権限チェック：承認者またはタスク作成者のみ更新可能
        $isApprover = $task->approver_user_id === $userId;
        $isCreator = $task->assigned_by_user_id === $userId;

        if (!$isApprover && !$isCreator) {
            throw new \Exception('このタスクを編集する権限がありません');
        }

        return DB::transaction(function () use ($task, $description) {
            $task->update([
                'description' => $description,
                'updated_by' => Auth::id(),
                'updated_at' => now(),
            ]);

            return $task->fresh();
        });
    }

    /**
     * @inheritDoc
     */
    public function searchTasks(User $user, string $searchType, array $searchTerms, string $operator): Collection
    {
        // リポジトリ層で検索を実行
        if ($searchType === 'tag') {
            $tasks = $this->taskRepository->searchByTags($user->id, $searchTerms, $operator);
        } else {
            $tasks = $this->taskRepository->searchByTitle($user->id, $searchTerms, $operator);
        }

        return $tasks;
    }
}