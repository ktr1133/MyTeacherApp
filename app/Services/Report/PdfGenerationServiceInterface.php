<?php

namespace App\Services\Report;

use App\Models\User;

/**
 * PDF生成サービスインターフェース
 */
interface PdfGenerationServiceInterface
{
    /**
     * メンバー別概況レポートのPDFを生成
     * 
     * @param User $targetUser 対象ユーザー
     * @param string $yearMonth 対象年月（YYYY-MM形式）
     * @param string $comment AIコメント
     * @param string|null $chartImageBase64 円グラフ画像（Base64）
     * @return string PDFバイナリデータ
     * @throws \RuntimeException PDF生成に失敗した場合
     */
    public function generateMemberSummaryPdf(
        User $targetUser,
        string $yearMonth,
        string $comment,
        ?string $chartImageBase64 = null
    ): string;
}
