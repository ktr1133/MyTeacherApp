<?php

namespace App\Http\Actions\Reports;

use App\Http\Requests\Reports\DownloadMemberSummaryPdfRequest;
use App\Services\Report\MonthlyReportServiceInterface;
use App\Services\Report\PdfGenerationServiceInterface;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * メンバー別概況レポートPDFダウンロードアクション
 */
class DownloadMemberSummaryPdfAction
{
    /**
     * コンストラクタ
     * 
     * @param MonthlyReportServiceInterface $reportService 月次レポートサービス
     * @param PdfGenerationServiceInterface $pdfService PDF生成サービス
     */
    public function __construct(
        protected MonthlyReportServiceInterface $reportService,
        protected PdfGenerationServiceInterface $pdfService
    ) {}
    
    /**
     * メンバー別概況レポートをPDFでダウンロード
     * 
     * @param DownloadMemberSummaryPdfRequest $request バリデーション済みリクエスト
     * @return Response|JsonResponse
     */
    public function __invoke(DownloadMemberSummaryPdfRequest $request): Response|JsonResponse
    {
        try {
            $userId = (int) $request->input('user_id');
            $yearMonth = $request->input('year_month');
            $comment = $request->input('comment');
            $chartImageBase64 = $request->input('chart_image');
            
            // 認証ユーザーとグループ取得
            $currentUser = $request->user();
            $targetUser = \App\Models\User::findOrFail($userId);
            
            // 認証ユーザーとグループ取得
            $currentUser = $request->user();
            $targetUser = \App\Models\User::findOrFail($userId);
            
            // PDF生成（Serviceに委譲）
            $pdfContent = $this->pdfService->generateMemberSummaryPdf(
                targetUser: $targetUser,
                yearMonth: $yearMonth,
                comment: $comment,
                chartImageBase64: $chartImageBase64
            );
            
            // ファイル名生成
            $fileName = $this->generateFileName($targetUser, $yearMonth);
            
            // PDFレスポンス返却
            return response()->make(
                $pdfContent,
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                    'Pragma' => 'no-cache',
                    'Expires' => '0',
                ]
            );
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('PDF download validation error', [
                'request_id' => $request->id(),
                'ip' => $request->ip(),
                'errors' => $e->errors(),
            ]);
            
            return response()->json([
                'error' => 'バリデーションエラーが発生しました。',
                'errors' => $e->errors(),
            ], 422);
            
        } catch (\RuntimeException $e) {
            Log::error('Member summary PDF generation failed', [
                'user_id' => $userId ?? null,
                'year_month' => $yearMonth ?? null,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'error' => 'PDF生成に失敗しました: ' . $e->getMessage(),
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('Unexpected error in PDF download', [
                'user_id' => $userId ?? null,
                'year_month' => $yearMonth ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'error' => '予期しないエラーが発生しました。',
            ], 500);
        }
    }
    
    /**
     * PDFファイル名を生成
     * 
     * @param \App\Models\User $user 対象ユーザー
     * @param string $yearMonth 対象年月（YYYY-MM形式）
     * @return string ファイル名
     */
    protected function generateFileName(\App\Models\User $user, string $yearMonth): string
    {
        $timestamp = now()->format('YmdHis');
        $yearMonthFormatted = str_replace('-', '', $yearMonth); // 2025-11 → 202511
        
        return sprintf(
            '%s_%s_%s.pdf',
            $user->username,
            $yearMonthFormatted,
            $timestamp
        );
    }
}
