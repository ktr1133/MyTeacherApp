<?php

namespace App\Http\Actions\Task;

use App\Services\Task\TaskManagementServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * ユーザーが入力したタスクを通常保存するアクション。
 */
class StoreTaskAction
{
    protected TaskManagementServiceInterface $service;

    /**
     * コンストラクタ。タスク管理サービスインターフェースを注入。
     */
    public function __construct(
        TaskManagementServiceInterface $service
    ) {
        $this->task_management_service = $service;
    }

    /**
     * タスクをDBに保存し、リダイレクトする。
     *
     * @param Request $request POSTリクエスト
     * @return RedirectResponse メインメニューへのリダイレクト
     */
    public function __invoke(Request $request): RedirectResponse
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
            logger()->info('Generated group_task_id: ' . $data['group_task_id']);
            // タスクの所有者は担当者
            $userId = $data['assigned_user_id'];
            // 担当者が未設定の場合は、グループの編集権限のないメンバー全員宛にタスクを作成する
            $groupFlg = is_null($userId) ? true : false;
            unset($data['assigned_user_id']);
        }

        $user = isset($userId) && !is_null($userId) ? $this->task_management_service->getUserById($userId) : Auth::user();

        // 2. Serviceに処理を委譲
        $this->task_management_service->createTask($user, $data, $groupFlg);

        // 3. 成功メッセージと共にリダイレクト
        return redirect()->route('dashboard')->with('success', 'タスクが登録されました。');
    }
}