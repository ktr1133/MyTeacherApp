<?php

namespace App\Http\Actions\Api\Report;

use App\Services\Report\MonthlyReportServiceInterface;
use App\Http\Responders\Api\Report\ReportApiResponder;
use App\Http\Requests\Api\Report\GenerateMemberSummaryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * メンバー別概況レポート生成API
 * 
 * POST /api/v1/reports/monthly/member-summary
 * 
 * 指定メンバーの月次概況レポートを生成（AIコメント付き）
 */
class GenerateMemberSummaryApiAction
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
     * メンバー別概況レポート生成
     * 
     * @param GenerateMemberSummaryRequest $request
     * @return JsonResponse
     */
    public function __invoke(GenerateMemberSummaryRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $currentUser = $request->user();
            
            // 権限チェック: グループマスターまたは本人のみ
            if (!$currentUser->canEditGroup() && $currentUser->id !== $data['user_id']) {
                return $this->responder->error('このレポートを生成する権限がありません。', 403);
            }
            
            // メンバー別概況レポート生成
            $summary = $this->reportService->generateMemberSummary(
                $data['user_id'],
                $data['group_id'],
                $data['year_month']
            );
            
            return $this->responder->memberSummary([
                'summary' => $summary,
                'user_id' => $data['user_id'],
                'group_id' => $data['group_id'],
                'year_month' => $data['year_month'],
            ]);
            
        } catch (\RuntimeException $e) {
            Log::error('Failed to generate member summary', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'data' => $request->validated(),
            ]);
            
            return $this->responder->error('レポート生成に失敗しました: ' . $e->getMessage(), 500);
        }
    }
}
