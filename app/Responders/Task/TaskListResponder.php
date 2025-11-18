<?php

namespace App\Responders\Task;

use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * TaskListServiceからのデータを受け取り、ビューを構築して返すレスポンダ。
 */
class TaskListResponder
{
    /**
     * タスクデータを受け取り、メインメニュービューを構築して返す。
     *
     * @param array $data データ
     * @return View 'dashboard'ビューにデータを渡したLaravel Viewオブジェクト
     */
    public function respond(array $data): View
    {
        return view('dashboard', [
            'tasks'             => $data['tasks'],
            'tags'              => $data['tags'],
            'notificationCount' => $data['notificationCount'] ?? 0,
        ]);
    }
}