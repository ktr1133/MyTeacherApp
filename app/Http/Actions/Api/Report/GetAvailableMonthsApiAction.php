<?php

namespace App\Http\Actions\Api\Report;

use App\Services\Report\MonthlyReportServiceInterface;
use App\Http\Responders\Api\Report\ReportApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 利用可能な月リスト取得API
 * 
 * GET /api/reports/monthly/available-months
 * 
 * 生成済みの月次レポートの年月リストを取得
 */
class GetAvailableMonthsApiAction
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
     * 利用可能な月リスト取得
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $group = $user->group;
            
            if (!$group) {
                return $this->responder->error('グループが見つかりません。', 404);
            }
            
            // 利用可能な月リスト取得（過去12ヶ月）
            $availableMonths = $this->reportService->getAvailableMonths($group, 12);
            
            return response()->json([
                'message' => '利用可能な月リストを取得しました。',
                'data' => $availableMonths,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('利用可能な月リスト取得エラー', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('月リストの取得に失敗しました。', 500);
        }
    }
}
