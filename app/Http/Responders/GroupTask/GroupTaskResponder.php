<?php

namespace App\Http\Responders\GroupTask;

use Illuminate\View\View;
use Illuminate\Support\Collection;

/**
 * グループタスクResponder
 * 
 * グループタスク関連のレスポンス整形を担当
 */
class GroupTaskResponder
{
    /**
     * グループタスク一覧画面を返す
     *
     * @param Collection $groupTasks
     * @return View
     */
    public function index(Collection $groupTasks): View
    {
        return view('group-tasks.index', [
            'groupTasks' => $groupTasks,
        ]);
    }

    /**
     * グループタスク編集画面を返す
     *
     * @param array $groupTask
     * @return View
     */
    public function edit(array $groupTask): View
    {
        return view('group-tasks.edit', [
            'groupTask' => $groupTask,
        ]);
    }
}
