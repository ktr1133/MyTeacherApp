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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
        try {
            Log::info('StoreTaskAction開始', [
                'user_id' => Auth::id(),
                'is_group_task' => $request->boolean('is_group_task'),
                'request_data' => $request->except(['_token']),
            ]);

            $data = $request->validated();

            // グループタスクの場合、追加フィールドを設定
            $groupFlg = false;
            if ($request->isGroupTask()) {
                // グループタスク作成権限チェック
                $user = Auth::user();
                
                Log::info('グループタスク作成権限チェック', [
                    'user_id' => $user->id,
                    'group_id' => $user->group_id,
                    'can_edit_group' => $this->groupService->canEditGroup($user),
                ]);
                
                if (!$this->groupService->canEditGroup($user) || !$user->group_id) {
                    Log::warning('グループタスク作成権限なし', [
                        'user_id' => $user->id,
                        'group_id' => $user->group_id,
                    ]);
                    abort(403, 'グループタスク作成権限がありません。');
                }

                // グループを取得
                $group = $user->group;
                
                Log::info('グループタスク制限チェック', [
                    'group_id' => $group->id,
                ]);
                
                // グループタスク作成数の制限チェック
                if (!$this->groupTaskLimitService->canCreateGroupTask($group)) {
                    $usage = $this->groupTaskLimitService->getGroupTaskUsage($group);
                    $message = sprintf(
                        '今月のグループタスク作成数が上限（%d件）に達しました。プレミアムプランにアップグレードすると無制限でグループタスクを作成できます。',
                        $usage['limit']
                    );
                    
                    Log::warning('グループタスク作成数上限に達した', [
                        'group_id' => $group->id,
                        'usage' => $usage,
                    ]);
                    
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
                
                Log::info('グループタスクデータ準備完了', [
                    'group_task_id' => $data['group_task_id'],
                    'assigned_user_id' => $userId,
                    'requires_approval' => $data['requires_approval'],
                ]);
            }

            $user = isset($userId) && !is_null($userId) 
                ? $this->taskManagementService->getUserById($userId) 
                : Auth::user();

            Log::info('タスク作成開始', [
                'user_id' => $user->id,
                'groupFlg' => $groupFlg,
            ]);

            DB::beginTransaction();
            
            $task = $this->taskManagementService->createTask($user, $data, $groupFlg);
            
            Log::info('タスク作成完了', [
                'task_id' => $task->id,
            ]);
            
            // グループタスク作成カウンターを増加
            if ($groupFlg && isset($group)) {
                $this->groupTaskLimitService->incrementGroupTaskCount($group);
                Log::info('グループタスクカウンター増加', [
                    'group_id' => $group->id,
                ]);
            }
            
            // 承認不要のグループタスクの場合は自動承認
            if ($groupFlg && !$task->requires_approval) {
                // 承認者（assigned_by_user_id）を取得して自動承認
                $approver = $this->profileService->findUserById($task->assigned_by_user_id);
                if ($approver) {
                    Log::info('自動承認実行', [
                        'task_id' => $task->id,
                        'approver_id' => $approver->id,
                    ]);
                    $this->taskApprovalService->approveTaskWithoutNotification($task, $approver);
                }
            }

            DB::commit();
            
            Log::info('StoreTaskAction完了', [
                'task_id' => $task->id,
                'groupFlg' => $groupFlg,
            ]);

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
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('StoreTaskActionでエラー発生', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'request_data' => $request->except(['_token']),
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'タスクの登録に失敗しました。システム管理者にお問い合わせください。',
                    'error' => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }
            
            return redirect()->back()
                ->withErrors(['error' => 'タスクの登録に失敗しました。もう一度お試しください。'])
                ->withInput();
        }
    }
}