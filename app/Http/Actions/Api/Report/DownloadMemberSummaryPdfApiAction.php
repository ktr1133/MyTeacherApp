<?php

namespace App\Http\Actions\Api\Report;

use App\Services\Report\MonthlyReportServiceInterface;
use App\Services\Report\PdfGenerationService;
use App\Http\Requests\Api\Report\GenerateMemberSummaryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * メンバー別概況レポートPDFダウンロードAPI
 * 
 * POST /api/v1/reports/monthly/member-summary/pdf
 * 
 * 指定メンバーの月次概況レポートをPDF形式で生成・ダウンロード
 */
class DownloadMemberSummaryPdfApiAction
{
    /**
     * コンストラクタ
     * 
     * @param MonthlyReportServiceInterface $reportService レポートサービス
     * @param PdfGenerationService $pdfService PDF生成サービス
     */
    public function __construct(
        protected MonthlyReportServiceInterface $reportService,
        protected PdfGenerationService $pdfService
    ) {}
    
    /**
     * メンバー別概況レポートPDF生成・ダウンロード
     * 
     * @param GenerateMemberSummaryRequest $request
     * @return Response|JsonResponse
     */
    public function __invoke(GenerateMemberSummaryRequest $request): Response|JsonResponse
    {
        try {
            $data = $request->validated();
            $currentUser = $request->user();
            
            // ユーザーオブジェクト取得
            $targetUser = \App\Models\User::findOrFail($data['user_id']);
            
            // 権限チェック: グループマスターまたは本人のみ
            if (!$currentUser->canEditGroup() && $currentUser->id !== $targetUser->id) {
                return response()->json([
                    'message' => 'このレポートをダウンロードする権限がありません。',
                ], 403);
            }
            
            // リクエストから必要なデータ取得
            $yearMonth = $data['year_month'];
            $comment = $data['comment'] ?? null;
            $chartImageBase64 = $data['chart_image'] ?? null;
            
            // commentが渡されていない場合は、その場で生成
            if (empty($comment)) {
                Log::info('PDF generation: Generating new AI comment', [
                    'user_id' => $targetUser->id,
                    'year_month' => $yearMonth,
                ]);
                
                $summaryData = $this->reportService->generateMemberSummary(
                    $targetUser->id,
                    $data['group_id'],
                    $yearMonth
                );
                $comment = $summaryData['comment'];
            }
            
            // PDF生成
            $pdfContent = $this->pdfService->generateMemberSummaryPdf(
                targetUser: $targetUser,
                yearMonth: $yearMonth,
                comment: $comment,
                chartImageBase64: $chartImageBase64
            );
            
            // ファイル名生成
            $fileName = sprintf(
                'member-summary-%s-%s.pdf',
                $data['year_month'],
                $data['user_id']
            );
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
            
        } catch (\RuntimeException $e) {
            Log::error('Failed to generate member summary PDF', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'data' => $request->validated(),
            ]);
            
            return response()->json([
                'message' => 'PDF生成に失敗しました: ' . $e->getMessage(),
            ], 500);
        }
    }
}
