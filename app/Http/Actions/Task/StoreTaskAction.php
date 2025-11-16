<?php

namespace App\Http\Actions\Task;

use App\Services\Task\TaskManagementServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * ユーザーが入力したタスクを通常保存するアクション。
 */
class StoreTaskAction
{
    protected TaskManagementServiceInterface $taskManagementService;

    /**
     * コンストラクタ。タスク管理サービスインターフェースを注入。
     */
    public function __construct(
        TaskManagementServiceInterface $taskManagementService
    ) {
        $this->taskManagementService = $taskManagementService;
    }

    /**
     * タスクをDBに保存し、リダイレクトする。
     *
     * @param Request $request POSTリクエスト
     * @return RedirectResponse|JsonResponse
     */
    public function __invoke(Request $request): RedirectResponse|JsonResponse
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'span' => ['required', 'integer', 'in:1,2,3'],
            'due_date' => ['nullable', 'string'],
            'priority' => ['nullable', 'integer', 'between:1,5'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
        
        // グループタスクの場合の追加バリデーション
        if ($request->boolean('is_group_task')) {
            $rules['assigned_user_id'] = ['nullable', 'integer', 'exists:users,id'];
            $rules['reward'] = ['required', 'integer', 'min:0'];
            $rules['requires_approval'] = ['required', 'boolean'];
        }

        $data = $request->validate($rules);

        // グループタスクの場合、追加フィールドを設定
        $groupFlg = false;
        if ($request->boolean('is_group_task')) {
            // グループタスク作成
            $user = Auth::user();
            if (!$user->canEditGroup() || !$user->group_id) {
                abort(403, 'グループタスク作成権限がありません。');
            }

            $data['user_id'] = $data['assigned_user_id'] ?? null;

            // 共通識別子を生成
            $data['group_task_id'] = (string) Str::uuid();

            // タスクの所有者は担当者
            $userId = $data['assigned_user_id'];
            // 担当者が未設定の場合は、グループの編集権限のないメンバー全員宛にタスクを作成する
            $groupFlg = is_null($userId) ? true : false;
        }

        $user = isset($userId) && !is_null($userId) ? $this->taskManagementService->getUserById($userId) : Auth::user();

        $this->taskManagementService->createTask($user, $data, $groupFlg);

        $msg = $groupFlg ? 'グループタスクが登録されました。' : 'タスクが登録されました。';

        $avatar_event = $groupFlg ? config('const.avatar_events.group_task_created') : config('const.avatar_events.task_created');

        if ($groupFlg) {
            session()->flash('avatar_event', $avatar_event);
        }

        // アバターイベント発火用のセッションをセットしてリダイレクト
        $route = !$groupFlg
            ? redirect()->route('dashboard')
                ->with('success', $msg)
                ->with('avatar_event', $avatar_event)
            : response()->json([
                'message' => $msg,
                'avatar_event' => $avatar_event,
            ]);

        return $route;
    }
}