<?php

namespace App\Responders\Admin;

/**
 * 無料トークンを更新する処理のレスポンダ
 */
class FreeTokenSettingResponder implements FreeTokenSettingResponderInterface
{
    public function redirectWithSuccess()
    {
        return redirect()
            ->route('admin.token-packages')
            ->with('success', '無料トークン数を更新しました');
    }
}