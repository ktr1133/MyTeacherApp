<?php

namespace App\Repositories\Report;

use App\Models\Group;
use App\Models\MonthlyReport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 月次レポートリポジトリ実装
 * 
 * Eloquent ORMを使用した月次レポートデータの永続化
 */
class MonthlyReportEloquentRepository implements MonthlyReportRepositoryInterface
{
    /**
     * 月次レポートを作成
     * 
     * @param array $data レポートデータ
     * @return MonthlyReport 作成されたレポート
     */
    public function create(array $data): MonthlyReport
    {
        return MonthlyReport::create($data);
    }
    
    /**
     * 月次レポートを更新
     * 
     * @param MonthlyReport $report 更新対象レポート
     * @param array $data 更新データ
     * @return MonthlyReport 更新されたレポート
     */
    public function update(MonthlyReport $report, array $data): MonthlyReport
    {
        $report->update($data);
        return $report->fresh();
    }
    
    /**
     * グループと年月でレポートを検索
     * 
     * @param int $groupId グループID
     * @param string $yearMonth 年月（YYYY-MM形式）
     * @return MonthlyReport|null レポート（存在しない場合null）
     */
    public function findByGroupAndMonth(int $groupId, string $yearMonth): ?MonthlyReport
    {
        // report_monthカラムは date型で YYYY-MM-01 形式で保存されている
        $reportMonth = $yearMonth . '-01';
        
        return MonthlyReport::where('group_id', $groupId)
            ->where('report_month', $reportMonth)
            ->first();
    }
    
    /**
     * グループのレポート一覧を取得（新しい順）
     * 
     * @param int $groupId グループID
     * @param int|null $limit 取得件数制限
     * @return Collection<MonthlyReport> レポート一覧
     */
    public function getByGroup(int $groupId, ?int $limit = null): Collection
    {
        $query = MonthlyReport::where('group_id', $groupId)
            ->orderBy('report_month', 'desc');
        
        if ($limit !== null) {
            $query->limit($limit);
        }
        
        return $query->get();
    }
    
    /**
     * グループが指定年月のレポートを持っているか確認
     * 
     * @param int $groupId グループID
     * @param string $yearMonth 年月（YYYY-MM形式）
     * @return bool レポートが存在する場合true
     */
    public function existsForGroupAndMonth(int $groupId, string $yearMonth): bool
    {
        $reportMonth = $yearMonth . '-01';
        
        return MonthlyReport::where('group_id', $groupId)
            ->where('report_month', $reportMonth)
            ->exists();
    }
    
    /**
     * 前月のレポートを取得
     * 
     * @param int $groupId グループID
     * @param string $yearMonth 基準年月（YYYY-MM形式）
     * @return MonthlyReport|null 前月のレポート（存在しない場合null）
     */
    public function getPreviousMonthReport(int $groupId, string $yearMonth): ?MonthlyReport
    {
        $reportMonth = $yearMonth . '-01';
        $previousMonth = date('Y-m-01', strtotime($reportMonth . ' -1 month'));
        
        return MonthlyReport::where('group_id', $groupId)
            ->where('report_month', $previousMonth)
            ->first();
    }
    
    /**
     * 指定期間より古いレポートを削除
     * 
     * @param \Carbon\Carbon $date この日付より古いレポートを削除
     * @return int 削除されたレポート数
     */
    public function deleteOlderThan(\Carbon\Carbon $date): int
    {
        return MonthlyReport::where('report_month', '<', $date->format('Y-m-01'))
            ->delete();
    }
    
    /**
     * 全グループを取得（レポート生成用）
     * 
     * @return Collection<Group> 全グループ
     */
    public function getAllGroups(): Collection
    {
        return Group::all();
    }
}
