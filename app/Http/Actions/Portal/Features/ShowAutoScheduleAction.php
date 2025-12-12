<?php

namespace App\Http\Actions\Portal\Features;

/**
 * 自動スケジュール機能詳細ページを表示するAction
 * 
 * ポータルサイトの機能紹介セクションにおける
 * 自動スケジュール機能の詳細説明ページを表示する。
 */
class ShowAutoScheduleAction
{
    /**
     * 自動スケジュール機能詳細ページを表示
     * 
     * @return \Illuminate\View\View
     */
    public function __invoke()
    {
        return view('portal.features.auto-schedule');
    }
}
