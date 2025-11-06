<?php

namespace App\Http\Actions\Task;

use App\Repositories\Task\TaskRepositoryInterface; // ★ リポジトリインターフェースをインポート
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * タスク入力（新規作成）画面を表示するアクション。
 */
class CreateTaskAction
{
    protected TaskRepositoryInterface $taskRepository; // ★ プロパティ追加

    /**
     * コンストラクタ。タスクリポジトリインターフェースを注入する。
     *
     * @param TaskRepositoryInterface $taskRepository タグデータ取得用のリポジトリ
     */
    public function __construct(TaskRepositoryInterface $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    /**
     * タスク入力画面を表示する。
     *
     * @param Request $request
     * @return View タスク入力フォームビュー
     */
    public function __invoke(Request $request): View
    {
        // フォームに必要な既存のタグデータをリポジトリから取得
        $tags = $this->taskRepository->getAllTags(); // ★ 動的にタグを取得
        
        return view('tasks.create', compact('tags'));
    }
}