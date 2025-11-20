<?php

namespace App\Responders\Admin;

class TokenPackageResponder
{
    /**
     * トークンパッケージ一覧画面を表示
     *
     * @param array $data
     * @return \Illuminate\Contracts\View\View
     */
    public function index($data)
    {
        return view('admin.token-packages-index', $data);
    }

    /**
     * トークンパッケージ作成画面を表示
     *
     * @param array $data
     * @return \Illuminate\Contracts\View\View
     */
    public function create($data = [])
    {
        return view('admin.token-packages-create', $data);
    }

    /**
     * トークンパッケージ編集画面を表示
     *
     * @param array $data
     * @return \Illuminate\Contracts\View\View
     */
    public function edit($data)
    {
        return view('admin.token-packages-edit', $data);
    }
}