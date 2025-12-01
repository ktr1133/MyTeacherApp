<?php

namespace App\Services\Report;

use App\Models\Group;
use App\Models\MonthlyReport;
use Illuminate\Support\Collection;

/**
 * 月次レポートサービスインターフェース
 * 
 * 月次レポート生成・取得・データ整形のビジネスロジックを定義
 */
interface MonthlyReportServiceInterface
{
    /**
     * グループの月次レポートを生成
     * 
     * @param Group $group 対象グループ
     * @param string $yearMonth 対象年月（YYYY-MM形式）
     * @return MonthlyReport 生成されたレポート
     * @throws \RuntimeException レポート生成失敗時
     */
    public function generateMonthlyReport(Group $group, string $yearMonth): MonthlyReport;
    
    /**
     * グループの月次レポート一覧を取得
     * 
     * @param Group $group 対象グループ
     * @param int|null $limit 取得件数制限
     * @return Collection<MonthlyReport> レポート一覧
     */
    public function getReportsForGroup(Group $group, ?int $limit = null): Collection;
    
    /**
     * 月次レポートを取得（サブスクリプション権限チェック含む）
     * 
     * @param Group $group 対象グループ
     * @param string $yearMonth 対象年月（YYYY-MM形式）
     * @return MonthlyReport|null レポート（権限がない場合null）
     */
    public function getMonthlyReport(Group $group, string $yearMonth): ?MonthlyReport;
    
    /**
     * グループが指定年月のレポートにアクセス可能か判定
     * 
     * @param Group $group 対象グループ
     * @param string $yearMonth 対象年月（YYYY-MM形式）
     * @return bool アクセス可能な場合true
     */
    public function canAccessReport(Group $group, string $yearMonth): bool;
    
    /**
     * レポートデータを表示用に整形
     * 
     * @param MonthlyReport $report レポート
     * @return array 整形されたデータ
     */
    public function formatReportForDisplay(MonthlyReport $report): array;
    
    /**
     * 全グループの月次レポートを一括生成
     * 
     * @param string $yearMonth 対象年月（YYYY-MM形式、省略時は先月）
     * @return array ['success' => int, 'failed' => int, 'errors' => array]
     */
    public function generateReportsForAllGroups(?string $yearMonth = null): array;
    
    /**
     * 古いレポートを削除（1年以上前）
     * 
     * @return int 削除されたレポート数
     */
    public function cleanupOldReports(): int;
}
