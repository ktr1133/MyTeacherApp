<?php

namespace App\Http\Actions\Reports;

use App\Services\Report\MonthlyReportServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 月次レポート詳細表示アクション
 */
class ShowMonthlyReportAction
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
     * 月次レポート詳細を表示
     * 
     * @param Request $request
     * @param string|null $year 年（YYYY形式、省略時は前月）
     * @param string|null $month 月（MM形式、省略時は前月）
     * @return View
     */
    public function __invoke(Request $request, ?string $year = null, ?string $month = null): View
    {
        $user = $request->user();
        $group = $user->group;
        
        if (!$group) {
            abort(404, 'グループが見つかりません。');
        }
        
        // 年月パラメータの処理（デフォルト: 前月）
        if (!$year || !$month) {
            $targetDate = now()->subMonth();
            $year = $targetDate->format('Y');
            $month = $targetDate->format('m');
        }
        
        // 年月フォーマット検証
        $yearMonth = sprintf('%s-%s', $year, $month);
        try {
            $targetMonth = Carbon::createFromFormat('Y-m', $yearMonth);
        } catch (\Exception $e) {
            abort(400, '無効な年月形式です。');
        }
        
        // アクセス権限チェック
        if (!$this->reportService->canAccessReport($group, $yearMonth)) {
            return view('reports.monthly.locked', [
                'group' => $group,
                'yearMonth' => $yearMonth,
                'targetMonth' => $targetMonth,
            ]);
        }
        
        // レポート取得
        $report = $this->reportService->getMonthlyReport($group, $yearMonth);
        
        // レポート未生成の場合
        if (!$report) {
            return view('reports.monthly.not-found', [
                'group' => $group,
                'yearMonth' => $yearMonth,
                'targetMonth' => $targetMonth,
            ]);
        }
        
        // 表示用にフォーマット
        $formatted = $this->reportService->formatReportForDisplay($report);
        
        // 選択可能な年月リスト（過去12ヶ月）
        $availableMonths = $this->reportService->getAvailableMonths($group, 12);
        
        return view('reports.monthly.show', [
            'group' => $group,
            'report' => $report,
            'formatted' => $formatted,
            'targetMonth' => $targetMonth,
            'yearMonth' => $yearMonth,
            'availableMonths' => $availableMonths,
        ]);
    }
}
