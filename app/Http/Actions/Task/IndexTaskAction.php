<?php

namespace App\Http\Actions\Task;

use App\Services\Task\TaskListServiceInterface;
use App\Services\Task\TaskApprovalServiceInterface;
use Illuminate\Support\Facades\Auth;
use App\Services\Tag\TagServiceInterface;
use App\Responders\Task\TaskListResponder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Tag;

/**
 * メインメニュー画面 (タスク一覧) の表示を処理するアクションクラス。
 * * HTTPリクエストを受け付け、TaskListServiceに処理を委譲し、
 * TaskListResponderを通じてビューを返却する、ルーティングとレスポンスの橋渡し役を担う。
 */
class IndexTaskAction
{
    protected TaskListServiceInterface $taskListService;
    protected TagServiceInterface $tagService;
    protected TaskApprovalServiceInterface $taskApprovalService;
    protected TaskListResponder $responder;

    /**
     * コンストラクタ。依存性の注入によりサービスとレスポンダを受け取る。
     *
     * @param TaskListService $service タスク一覧のビジネスロジックを提供するサービス
     * @param TagServiceInterface $tag_service タグ関連のビジネスロジックを提供するサービス
     * @param TaskListResponder $responder ビューの構築とHTTP応答を担当するレスポンダ
     */
    public function __construct(
        TaskListServiceInterface $taskListService,
        TagServiceInterface $tagService,
        TaskApprovalServiceInterface $taskApprovalService,
        TaskListResponder $responder
    )
    {
        $this->taskListService = $taskListService;
        $this->tagService = $tagService;
        $this->taskApprovalService = $taskApprovalService;
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
        $filters = $request->only(['search', 'status', 'priority', 'tags']);
        
        // タスクデータを取得
        $tasks = $this->taskListService->getTasksForUser($userId, $filters);
        $tags = $this->tagService->getByUserId($userId);
        // Responderでビューを構築し、返却
        return $this->responder->respond([
            'tasks'                 => $tasks,
            'tags'                  => $tags,
        ]);
    }
}