<?php

namespace App\Http\Actions\Api\Report;

use App\Services\Report\MonthlyReportServiceInterface;
use App\Http\Responders\Api\Report\ReportApiResponder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 月次レポート詳細取得API
 * 
 * GET /api/v1/reports/monthly/{year}/{month}
 * 
 * 指定年月の月次レポート詳細データを取得
 */
class ShowMonthlyReportApiAction
{
    /**
     * コンストラクタ
     * 
     * @param MonthlyReportServiceInterface $reportService レポートサービス
     * @param ReportApiResponder $responder レスポンダー
     */
    public function __construct(
        protected MonthlyReportServiceInterface $reportService,
        protected ReportApiResponder $responder
    ) {}
    
    /**
     * 月次レポート詳細取得
     * 
     * @param Request $request
     * @param string|null $year 年（YYYY形式、省略時は前月）
     * @param string|null $month 月（MM形式、省略時は前月）
     * @return JsonResponse
     */
    public function __invoke(Request $request, ?string $year = null, ?string $month = null): JsonResponse
    {
        $user = $request->user();
        $group = $user->group;
        
        if (!$group) {
            return $this->responder->error('グループが見つかりません。', 404);
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
            return $this->responder->error('無効な年月形式です。', 400);
        }
        
        // アクセス権限チェック
        if (!$this->reportService->canAccessReport($group, $yearMonth)) {
            return $this->responder->error('このレポートへのアクセス権限がありません。サブスクリプションが必要です。', 403, [
                'locked' => true,
                'year_month' => $yearMonth,
                'subscription_required' => true,
            ]);
        }
        
        // レポート取得
        $report = $this->reportService->getMonthlyReport($group, $yearMonth);
        
        // レポート未生成の場合
        if (!$report) {
            return $this->responder->error('レポートが見つかりません。', 404, [
                'year_month' => $yearMonth,
                'not_generated' => true,
            ]);
        }
        
        // 表示用にフォーマット
        $formatted = $this->reportService->formatReportForDisplay($report);
        
        // 選択可能な年月リスト（過去12ヶ月）
        $availableMonths = $this->reportService->getAvailableMonths($group, 12);
        
        // グラフ用のトレンドデータ取得（直近6ヶ月）
        $trendData = $this->reportService->getTrendData($group, $yearMonth, 6);

        return $this->responder->monthlyReport([
            'report' => $report,
            'formatted' => $formatted,
            'target_month' => $targetMonth->toDateString(),
            'year_month' => $yearMonth,
            'year' => $year,
            'month' => $month,
            'available_months' => $availableMonths,
            'trend_data' => $trendData,
        ]);
    }
}
