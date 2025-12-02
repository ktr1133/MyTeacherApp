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
    
    /**
     * 選択可能な年月リストを取得
     * 
     * @param Group $group 対象グループ
     * @param int $limit 最大件数（デフォルト: 12ヶ月）
     * @return array ['year_month' => 'YYYY-MM', 'label' => 'YYYY年MM月', 'has_report' => bool]
     */
    public function getAvailableMonths(Group $group, int $limit = 12): array;
    
    /**
     * グループの直近N ヶ月のトレンドグラフデータを取得
     * 
     * @param Group $group 対象グループ
     * @param string $yearMonth 基準年月（YYYY-MM形式）
     * @param int $months 取得月数（デフォルト: 6ヶ月）
     * @return array Chart.js用のデータセット ['labels' => [], 'datasets' => [], 'members' => []]
     */
    public function getTrendData(Group $group, string $yearMonth, int $months = 6): array;
    
    /**
     * メンバー別概況レポートPDF用データを生成
     * 
     * @param int $userId ユーザーID
     * @param int $groupId グループID
     * @param string $yearMonth 対象年月（YYYY-MM形式）
     * @return array PDF生成用データ ['userName', 'yearMonth', 'comment', 'normalTaskCount', 'groupTaskCount', 'changePercentage', 'topCategory', 'chartImageBase64', 'rewardTrend']
     * @throws \RuntimeException レポート生成失敗時
     */
    public function generateMemberSummaryPdfData(int $userId, int $groupId, string $yearMonth): array;
    
    /**
     * メンバー別概況レポートを生成
     * 
     * @param int $userId ユーザーID
     * @param int $groupId グループID
     * @param string $yearMonth 対象年月（YYYY-MM形式）
     * @return array ['comment' => string, 'task_classification' => array, 'reward_trend' => array, 'tokens_used' => int]
     * @throws \RuntimeException レポート生成失敗時
     */
    public function generateMemberSummary(int $userId, int $groupId, string $yearMonth): array;
}
