<?php

namespace App\Http\Actions\Portal\Features;

/**
 * 月次レポート機能詳細ページを表示するAction
 * 
 * ポータルサイトの機能紹介セクションにおける
 * 月次レポート機能の詳細説明ページを表示する。
 */
class ShowMonthlyReportAction
{
    /**
     * 月次レポート機能詳細ページを表示
     * 
     * @return \Illuminate\View\View
     */
    public function __invoke()
    {
        return view('portal.features.monthly-report');
    }
}
