<?php

namespace App\Services\Task;

use App\Models\Task;
use App\Models\User;
use App\Repositories\Profile\ProfileUserRepositoryInterface;
use App\Repositories\Tag\TagRepositoryInterface;
use App\Repositories\Task\TaskRepositoryInterface;
use App\Services\AI\OpenAIServiceInterface;
use App\Services\Notification\NotificationServiceInterface;
use App\Services\Tag\TagServiceInterface;
use App\Services\Task\TaskProposalServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;


/**
 * TaskManagementServiceInterface の具象クラス。
 */
class TaskManagementService implements TaskManagementServiceInterface
{
    protected TaskRepositoryInterface $taskRepository;
    protected TagRepositoryInterface $tagRepository;
    protected ProfileUserRepositoryInterface $profileUserRepository;
    protected OpenAIServiceInterface $openAIService;
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
        OpenAIServiceInterface $openAIService,
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
     * ユーザーのキャッシュをクリア（内部用）
     *
     * @param int $userId ユーザーID
     * @return void
     */
    private function clearUserCache(int $userId): void
    {
        try {
            Cache::tags(['dashboard', "user:{$userId}", 'tasks'])->flush();
        } catch (\Exception $e) {
            Log::error('Failed to clear cache', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * ユーザーのタスクキャッシュをクリア（公開API）
     * 
     * タスクの完了状態変更など、外部から直接タスクを更新した際に使用。
     *
     * @param int $userId ユーザーID
     * @return void
     */
    public function clearUserTaskCache(int $userId): void
    {
        $this->clearUserCache($userId);
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
    public function createTask(User $user, array $data, bool $groupFlg): ?Task
    {
        // タスク登録用データの作成
        $taskData = $this->makeTaskBaseData($data);
        $taskData['priority'] = $data['priority'] ?? 3;
        $taskData['is_completed'] = false;
        $is_charged = isset($data['assigned_user_id']) ? true : false;
        $task = null;

        DB::transaction(function () use ($user, $data, $taskData, $groupFlg, $is_charged, &$task) {
            // グループタスクの場合
            if ($groupFlg) {
                // 追加フィールドの設定
                $taskData['reward'] = $data['reward'];
                $taskData['requires_approval'] = $data['requires_approval'];
                $taskData['requires_image'] = $data['requires_image'] ?? false;
                $taskData['assigned_by_user_id'] = Auth::user()->id;
                $taskData['group_task_id'] = $data['group_task_id'];
                // 担当者が未設定の場合はグループの編集権限のないメンバー全員分にタスクを作成する
                if (!$is_charged) {
                    // グループメンバのうち、編集権限のないユーザを取得
                    $users = $this->profileUserRepository->getMembersWithoutEditPermission($user->id);
                    $createdTasks = [];
                    foreach ($users as $member) {
                        $taskData['user_id'] = $member->id;
                        $createdTask = $this->taskRepository->create($taskData);

                        // タグを関連付け（タグ名の配列）
                        if (isset($data['tags']) && is_array($data['tags'])) {
                            $this->taskRepository->syncTagsByName($createdTask, $data['tags']);
                        }
                        
                        // メンバーのキャッシュをクリア
                        $this->clearUserCache($member->id);
                        
                        $createdTasks[] = $createdTask;
                    }
                    // 最初に作成されたタスクを返す（代表として）
                    $task = $createdTasks[0] ?? null;
                    
                    // 編集権限のないメンバーに通知を送信
                    foreach ($users as $member) {
                        // ユーザーのテーマに応じてメッセージを変更
                        if ($member->useChildTheme()) {
                            $title = 'あたらしいタスクができたよ！';
                            $body = '「' . $data['title'] . '」というタスクができました。がんばってやってみよう！';
                        } else {
                            $title = '新しいグループタスクが作成されました';
                            $body = '新しいグループタスク「' . $data['title'] . '」が作成されました。タスクリストを確認してください。';
                        }
                        
                        $this->notificationService->sendNotification(
                            Auth::user()->id,
                            $member->id,
                            config('const.notification_types.group_task_created'),
                            $title,
                            $body,
                            'important'
                        );
                    }
                // 担当者が設定されている場合は担当者分のみタスクを作成
                } else {
                    $taskData['user_id'] = $data['assigned_user_id'];
                    $createdTask = $this->taskRepository->create($taskData);
                    // タグを関連付け（タグ名の配列）
                    if (isset($data['tags']) && is_array($data['tags'])) {
                        $this->taskRepository->syncTagsByName($createdTask, $data['tags']);
                    }
                    // 担当者のキャッシュをクリア
                    $this->clearUserCache($data['assigned_user_id']);
                    
                    $task = $createdTask;
                    
                    // 担当者に通知を送信
                    $assignedUser = $this->profileUserRepository->findById($data['assigned_user_id']);
                    
                    // ユーザーのテーマに応じてメッセージを変更
                    if ($assignedUser && $assignedUser->useChildTheme()) {
                        $title = 'あたらしいタスクができたよ！';
                        $body = '「' . $data['title'] . '」というタスクができました。がんばってやってみよう！';
                    } else {
                        $title = '新しいグループタスクが作成されました';
                        $body = '新しいグループタスク「' . $data['title'] . '」が作成されました。タスクリストを確認してください。';
                    }
                    
                    $this->notificationService->sendNotification(
                        Auth::user()->id,
                        $data['assigned_user_id'],
                        config('const.notification_types.group_task_created'),
                        $title,
                        $body,
                        'important'
                    );       
                }
            // 通常タスク登録の場合
            } else {
                // 通常タスクはログインユーザーが所有者
                $taskData['user_id'] = $user->id;
                $createdTask = $this->taskRepository->create($taskData);
                // タグを関連付け（タグ名の配列）
                if (isset($data['tags']) && is_array($data['tags'])) {
                    $tagNames = $this->tagRepository->findByIds($data['tags'])->pluck('name')->toArray();
                    $this->taskRepository->syncTagsByName($createdTask, $data['tags']);
                }
                // 作成者のキャッシュをクリア
                $this->clearUserCache($user->id);
                
                $task = $createdTask;
            }
        });
    
        return $task?->fresh(['tags']);
    }

    /**
     * タスク登録用の基本データを作成するヘルパーメソッド
     *
     * @param array $data 入力データ
     * @return array タスク登録用データ
     */
    public function makeTaskBaseData(array $data): array
    {
        // spanが存在し、中期の場合のみdue_date処理
        if (isset($data['span']) && $data['span'] == config('const.task_spans.mid')) {
            // due_dateが存在する場合のみ処理
            if (isset($data['due_date'])) {
                try {
                    // 更新の場合は既存のdue_dateをパース
                    $data['due_date'] = Carbon::parse($data['due_date'])->format('Y-m-d');
                } catch (\Exception $e) {
                    // 新規の場合はdue_dataは年末に設定
                    $tmp_due_data = $data['due_date'];
                    $data['due_date'] = Carbon::createFromFormat('Y', $tmp_due_data)->endOfYear()->format('Y-m-d');
                }
            }
        }

        // 更新時に存在するフィールドのみを含める
        $result = [];
        
        if (isset($data['title'])) {
            $result['title'] = $data['title'];
        }
        
        if (array_key_exists('description', $data)) {
            $result['description'] = $data['description'];
        }
        
        if (isset($data['span'])) {
            $result['span'] = $data['span'];
        }
        
        if (array_key_exists('due_date', $data)) {
            $result['due_date'] = $data['due_date'];
        }

        return $result;
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

            // キャッシュクリア
            $this->clearUserCache($task->user_id);

            return $task->fresh(['tags']);
        });
    }

    /**
     * @inheritDoc
     */
    public function deleteTask(Task $task): bool
    {
        try {
            $userId = $task->user_id;
            
            // タスクに関連する画像をS3から削除し、レコードも削除
            foreach ($task->images as $image) {
                if ($image->file_path && Storage::disk('s3')->exists($image->file_path)) {
                    Storage::disk('s3')->delete($image->file_path);
                }
                $image->delete(); // TaskImageレコードを削除
            }
            
            // リポジトリを使用してタスクを削除（ソフトデリート）
            $deleted = $this->taskRepository->deleteTask($task);

            if (!$deleted) {
                Log::warning('Task deletion returned false', ['task_id' => $task->id]);
            }

            // キャッシュクリア
            $this->clearUserCache($userId);

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

            // ユーザーのキャッシュをクリア（一括作成後に最新データを反映）
            $this->clearUserCache($user->id);

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