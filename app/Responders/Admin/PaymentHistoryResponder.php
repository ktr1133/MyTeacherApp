<?php

namespace App\Responders\Admin;

use Illuminate\Contracts\View\View;

/**
 * 課金履歴画面レスポンダー（管理者用）
 */
class PaymentHistoryResponder
{
    /**
     * レスポンスを返す
     *
     * @param array $data
     * @return View
     */
    public function response(array $data): View
    {
        return view('admin.payments.index', $data);
    }
}