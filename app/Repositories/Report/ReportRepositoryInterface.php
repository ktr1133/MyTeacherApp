<?php

namespace App\Repositories\Report;

use Carbon\Carbon;

/**
 * 実績レポート用のデータアクセスインターフェース
 * 
 * タスクの完了件数・未完了件数・報酬などを期間・ユーザー単位で取得します。
 */
interface ReportRepositoryInterface
{
    /**
     * 指定期間の通常タスク完了件数を日付ごとに取得
     * 
     * @param int $userId ユーザーID
     * @param Carbon $start 開始日時
     * @param Carbon $end 終了日時
     * @return array キー: 'Y-m-d', 値: 件数
     */
    public function getNormalCompletedCountsByDate(int $userId, Carbon $start, Carbon $end): array;

    /**
     * 指定期間の通常タスク未完了件数を期限日ごとに取得
     * 
     * @param int $userId ユーザーID
     * @param Carbon $start 開始日
     * @param Carbon $end 終了日
     * @return array キー: 'Y-m-d', 値: 件数
     */
    public function getNormalIncompleteCountsByDueDate(int $userId, Carbon $start, Carbon $end): array;

    /**
     * 指定期間のグループタスク承認済み件数を承認日ごとに取得
     * 
     * @param int $userId ユーザーID
     * @param Carbon $start 開始日時
     * @param Carbon $end 終了日時
     * @return array キー: 'Y-m-d', 値: 件数
     */
    public function getGroupCompletedCountsByDate(int $userId, Carbon $start, Carbon $end): array;

    /**
     * 指定期間のグループタスク未承認件数を期限日ごとに取得
     * 
     * @param int $userId ユーザーID
     * @param Carbon $start 開始日
     * @param Carbon $end 終了日
     * @return array キー: 'Y-m-d', 値: 件数
     */
    public function getGroupIncompleteCountsByDueDate(int $userId, Carbon $start, Carbon $end): array;

    /**
     * 指定期間のグループタスク報酬合計を承認日ごとに取得
     * 
     * @param int $userId ユーザーID
     * @param Carbon $start 開始日時
     * @param Carbon $end 終了日時
     * @return array キー: 'Y-m-d', 値: 報酬合計
     */
    public function getGroupRewardByDate(int $userId, Carbon $start, Carbon $end): array;
}