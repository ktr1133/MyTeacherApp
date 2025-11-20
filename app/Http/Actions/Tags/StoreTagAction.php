<?php

namespace App\Http\Actions\Tags;

use App\Services\Task\TaskManagementServiceInterface;
use App\Services\Tag\TagServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;

/**
 * ユーザーが入力したタスクを通常保存するアクション。
 */
class StoreTagAction
{
    protected TaskManagementServiceInterface $taskService;
    protected TagServiceInterface $tagService;

    /**
     * コンストラクタ。タスク管理サービスインターフェースを注入。
     */
    public function __construct(
        TaskManagementServiceInterface $taskService,
        TagServiceInterface $tagService
    ) {
        $this->taskService = $taskService;
        $this->tagService = $tagService;
    }

    /**
     * タグをDBに保存し、リダイレクトする。
     *
     * @param Request $request POSTリクエスト
     * @return RedirectResponse メインメニューへのリダイレクト
     */
    public function __invoke(Request $request): RedirectResponse
    {
        // バリデーション
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return redirect()->route('tags.list')->withErrors($validator)->withInput();
        }
        $validated = $validator->validated();

        // Serviceに処理を委譲
        $this->tagService->createTag($request->user(), $validated);

        // 成功メッセージと共にリダイレクト
        return redirect()
            ->route('tags.list')
            ->with('success', 'タグが登録されました。')
            ->with('avatar_event', config('const.avatar_events.tag_created'));
    }
}