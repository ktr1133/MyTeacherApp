<?php

namespace App\Http\Actions\Tags;

use App\Services\Tag\TagServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;

/**
 * ユーザーが入力したタスクを通常保存するアクション。
 */
class UpdateTagAction
{
    protected TagServiceInterface $tagService;

    /**
     * コンストラクタ。タスク管理サービスインターフェースを注入。
     */
    public function __construct(
        TagServiceInterface $tagService
    ) {
        $this->tagService = $tagService;
    }

    /**
     * タグをDBに保存し、リダイレクトする。
     *
     * @param Request $request POSTリクエスト
     * @param int $id タグID
     * @return RedirectResponse メインメニューへのリダイレクト
     */
    public function __invoke(Request $request, int $id): RedirectResponse
    {
        // バリデーション
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->route('tags.list')->withErrors($validator)->withInput();
        }
        $validated = $validator->validated();
        $validated['id'] = $id;

        // Serviceに処理を委譲
        $this->tagService->updateTag($request->user(), $validated);

        // 成功メッセージと共にリダイレクト
        return redirect()->route('tags.list')->with('success', 'タグが更新されました。');
    }
}