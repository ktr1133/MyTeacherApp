<?php

namespace App\Http\Actions\Tags;

use App\Services\Tag\TagServiceInterface;
use App\Repositories\Tag\TagRepositoryInterface;
use App\Responders\Tags\TagsListResponder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * メインメニュー画面 (タグ一覧) の表示を処理するアクションクラス。
 */
class TagsListAction
{
    protected TagServiceInterface $tagService;
    protected TagRepositoryInterface $tagRepository;
    protected TagsListResponder $responder;

    /**
     * コンストラクタ。依存性の注入によりサービスとレスポンダを受け取る。
     *
     * @param TagServiceInterface $tagService タグ関連のビジネスロジックを提供するサービス
     * @param TagRepositoryInterface $tagRepository タグリポジトリ（tasks_count取得用）
     * @param TagsListResponder $responder ビューの構築とHTTP応答を担当するレスポンダ
     */
    public function __construct(
        TagServiceInterface $tagService,
        TagRepositoryInterface $tagRepository,
        TagsListResponder $responder
    )
    {
        $this->tagService = $tagService;
        $this->tagRepository = $tagRepository;
        $this->responder = $responder;
    }

    /**
     * アクションの実行メソッド (__invoke)。GETリクエストを処理する。
     *
     * @param Request $request HTTPリクエストオブジェクト（認証済みユーザー情報、フィルタパラメータを含む）
     * @return Response|\Illuminate\View\View ビューを含むHTTPレスポンス
     */
    public function __invoke(Request $request): Response|\Illuminate\View\View
    {
        // ユーザーIDと検索・フィルタパラメータを取得
        $userId = $request->user()->id;

        // タグデータを取得（tasks_count付き）
        $tags = $this->tagRepository->getByUserIdWithTaskCount($userId);

        // タグに関連付けられたタスクを取得
        $tasks = $this->tagService->getTasksByUserId($userId);

        // ビューを構築し、返却
        return $this->responder->respond([
            'tags'  => $tags,
            'tasks' => $tasks,
        ]);
    }
}