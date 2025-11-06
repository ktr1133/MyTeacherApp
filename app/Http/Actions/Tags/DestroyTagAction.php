<?php

namespace App\Http\Actions\Tags;

use App\Services\Tag\TagServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;

/**
 * ユーザーが入力したタスクを通常保存するアクション。
 */
class DestroyTagAction
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
     * タグを削除するアクション。
     *
     * @param Request $request
     * @param int $id タグID
     * @return RedirectResponse
     */
    public function __invoke(Request $request, int $id): RedirectResponse
    {

        // バリデーション
        $validator = Validator::make(['id' => $id], [
            'id' => 'required|integer|exists:tags,id',
        ]);
        if ($validator->fails()) {
            return redirect()->route('tags.list')->withErrors($validator)->withInput();
        }
        $validated = $validator->validated();

        // Serviceに処理を委譲
        $result =$this->tagService->deleteTag($validated['id']);

        // 成功メッセージと共にリダイレクト
        if (!$result) {
            return redirect()->route('tags.list')->with('error', 'タグの削除に失敗しました。');
        }

        return redirect()->route('tags.list')->with('success', 'タグが削除されました。');
    }
}