<?php

namespace App\Responders\Tags;

use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * TagsServiceからのデータを受け取り、ビューを構築して返すレスポンダ。
 */
class TagsListResponder
{
    /**
     * タグデータを受け取り、メインメニュービューを構築して返す。
     *
     * @param array $data データ
     * @return View 'tags'ビューにデータを渡したLaravel Viewオブジェクト
     */
    public function respond(array $data): View
    {
        return view('tags-list', [
            'tags'  => $data['tags'],
            'tasks' => $data['tasks'],
        ]);
    }
}