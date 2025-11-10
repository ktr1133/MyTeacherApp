<?php

namespace App\Responders\Admin\User;

use Illuminate\Contracts\View\View;

class IndexUserResponder
{
    /**
     * ユーザー一覧画面を表示
     *
     * @param array $data
     * @return View
     */
    public function respond(array $data): View
    {
        return view('admin.index-user', [
            'users' => $data['users'],
            'stats' => $data['stats'],
        ]);
    }
}