<?php

namespace App\Http\Actions\Task;

use App\Http\Requests\Task\StoreTaskRequest;
use App\Services\Profile\GroupServiceInterface;
use App\Services\Profile\ProfileManagementServiceInterface;
use App\Services\Task\TaskManagementServiceInterface;
use App\Services\Task\TaskApprovalServiceInterface;
use App\Services\Group\GroupTaskLimitServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * ユーザーが入力したタスクを通常保存するアクション。
 */
class StoreTaskAction
{
    protected TaskManagementServiceInterface $taskManagementService;
    protected GroupServiceInterface $groupService;
    protected ProfileManagementServiceInterface $profileService;
    protected TaskApprovalServiceInterface $taskApprovalService;
    protected GroupTaskLimitServiceInterface $groupTaskLimitService;

    /**
     * コンストラクタ。タスク管理サービスインターフェースを注入。
     */
    public function __construct(
        TaskManagementServiceInterface $taskManagementService,
        GroupServiceInterface $groupService,
        ProfileManagementServiceInterface $profileService,
        TaskApprovalServiceInterface $taskApprovalService,
        GroupTaskLimitServiceInterface $groupTaskLimitService
    ) {
        $this->taskManagementService = $taskManagementService;
        $this->groupService = $groupService;
        $this->profileService = $profileService;
        $this->taskApprovalService = $taskApprovalService;
        $this->groupTaskLimitService = $groupTaskLimitService;
    }

    /**
     * タスクをDBに保存し、リダイレクトする。
     *
     * @param StoreTaskRequest $request POSTリクエスト
     * @return RedirectResponse|JsonResponse
     */
    public function __invoke(StoreTaskRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

        // グループタスクの場合、追加フィールドを設定
        $groupFlg = false;
        if ($request->isGroupTask()) {
            // グループタスク作成権限チェック
            $user = Auth::user();
            if (!$this->groupService->canEditGroup($user) || !$user->group_id) {
                abort(403, 'グループタスク作成権限がありません。');
            }

            // グループを取得
            $group = $user->group;
            
            // グループタスク作成数の制限チェック
            if (!$this->groupTaskLimitService->canCreateGroupTask($group)) {
                $usage = $this->groupTaskLimitService->getGroupTaskUsage($group);
                $message = sprintf(
                    '今月のグループタスク作成数が上限（%d件）に達しました。プレミアムプランにアップグレードすると無制限でグループタスクを作成できます。',
                    $usage['limit']
                );
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => $message,
                        'usage' => $usage,
                        'upgrade_required' => true,
                    ], 422);
                }
                
                return redirect()->back()
                    ->withErrors(['error' => $message])
                    ->withInput();
            }

            $data['user_id'] = $data['assigned_user_id'] ?? null;

            // 共通識別子を生成
            $data['group_task_id'] = (string) Str::uuid();

            // タスクの所有者は担当者
            $userId = $data['assigned_user_id'];

            // グループタスクフラグを立てる
            $groupFlg = true;

            // 承認要否を設定（チェックボックスがない場合はfalse）
            $data['requires_approval'] = $request->requiresApproval();
        }

        $user = isset($userId) && !is_null($userId) 
            ? $this->taskManagementService->getUserById($userId) 
            : Auth::user();

        $task = $this->taskManagementService->createTask($user, $data, $groupFlg);
        
        // グループタスク作成カウンターを増加
        if ($groupFlg && isset($group)) {
            $this->groupTaskLimitService->incrementGroupTaskCount($group);
        }
        
        // 承認不要のグループタスクの場合は自動承認
        if ($groupFlg && !$task->requires_approval) {
            // 承認者（assigned_by_user_id）を取得して自動承認
            $approver = $this->profileService->findUserById($task->assigned_by_user_id);
            if ($approver) {
                $this->taskApprovalService->approveTaskWithoutNotification($task, $approver);
            }
        }

        $msg = $groupFlg ? 'グループタスクが登録されました。' : 'タスクが登録されました。';

        $avatar_event = $groupFlg 
            ? config('const.avatar_events.group_task_created') 
            : config('const.avatar_events.task_created');

        // 通常タスク: リダイレクト（同期処理）
        if (!$groupFlg) {
            return redirect()->route('dashboard')
                ->with('success', $msg)
                ->with('avatar_event', $avatar_event);
        }

        // グループタスク: JSON レスポンス（非同期処理）
        return response()->json([
            'message' => $msg,
            'avatar_event' => $avatar_event,
        ]);
    }
}