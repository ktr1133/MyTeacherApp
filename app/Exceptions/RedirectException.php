<?php

namespace App\Exceptions;

use Exception;

/**
 * リダイレクトしてエラーメッセージを表示する処理
 */
class RedirectException extends Exception
{
    public function render($request)
    {
        // この例外が投げられたら自動的にここが実行される
        return redirect()->back()
            ->withInput()
            ->with('error', $this->getMessage()); // 例外時のメッセージをそのまま表示する場合
    }
}