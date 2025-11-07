<?php

namespace App\Responders\Batch;

use Illuminate\Contracts\View\View;

class ScheduledTaskResponder
{
    /**
     * 一覧画面レスポンス
     */
    public function index(array $data): View
    {
        return view('batch.index', $data);
    }

    /**
     * 作成画面レスポンス
     */
    public function create(array $data): View
    {
        return view('batch.create', $data);
    }

    /**
     * 編集画面レスポンス
     */
    public function edit(array $data): View
    {
        return view('batch.edit', $data);
    }

    /**
     * 実行履歴画面レスポンス
     */
    public function history(array $data): View
    {
        return view('batch.history', $data);
    }
}