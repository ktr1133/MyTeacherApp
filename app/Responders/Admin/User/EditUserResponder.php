<?php

namespace App\Responders\Admin\User;

use Illuminate\Contracts\View\View;

class EditUserResponder
{
    /**
     * ユーザー編集画面を表示
     *
     * @param array $data
     * @return View
     */
    public function respond(array $data): View
    {
        return view('admin.edit-user', [
            'user' => $data['user'],
        ]);
    }
}