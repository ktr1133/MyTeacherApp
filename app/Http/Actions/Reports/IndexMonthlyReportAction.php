<?php

namespace App\Http\Actions\Reports;

use App\Services\Report\MonthlyReportServiceInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 月次レポート一覧表示アクション
 */
class IndexMonthlyReportAction
{
    /**
     * コンストラクタ
     * 
     * @param MonthlyReportServiceInterface $reportService
     */
    public function __construct(
        protected MonthlyReportServiceInterface $reportService
    ) {}
    
    /**
     * 月次レポート一覧を表示
     * 
     * @param Request $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $group = $user->group;
        
        if (!$group) {
            abort(404, 'グループが見つかりません。');
        }
        
        // グループの月次レポート一覧を取得（最新12件）
        $reports = $this->reportService->getReportsForGroup($group, 12);
        
        // 各レポートのアクセス権限をチェック
        $reportsWithAccess = $reports->map(function ($report) use ($group) {
            $yearMonth = $report->report_month->format('Y-m');
            $canAccess = $this->reportService->canAccessReport($group, $yearMonth);
            
            return [
                'report' => $report,
                'can_access' => $canAccess,
                'formatted' => $canAccess ? $this->reportService->formatReportForDisplay($report) : null,
            ];
        });
        
        return view('reports.monthly.index', [
            'group' => $group,
            'reports' => $reportsWithAccess,
        ]);
    }
}
