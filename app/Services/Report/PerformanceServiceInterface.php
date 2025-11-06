<?php

namespace App\Services\Report;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * 実績レポート集計サービスのインターフェース
 * 
 * ユーザーのタスク実績（通常/グループタスク）を週間・月間・年間で集計し、
 * グラフや表で可視化するためのデータを提供します。
 */
interface PerformanceServiceInterface
{
    /**
     * 週間実績を取得する（月曜開始・日曜終了）
     * 
     * @param User $user 対象ユーザー
     * @return array 日別の完了件数・未完了件数・累積完了件数・グループタスク報酬累積を含む配列
     *               - labels: array 日付ラベル（例: '11/4'）
     *               - nDone: array 通常タスク完了件数（日別）
     *               - nTodo: array 通常タスク未完了件数（日別）
     *               - nCum: array 通常タスク累積完了件数
     *               - gDone: array グループタスク完了件数（日別）
     *               - gTodo: array グループタスク未完了件数（日別）
     *               - gCum: array グループタスク累積完了件数
     *               - gReward: array グループタスク報酬（日別）
     *               - gRewardCum: array グループタスク報酬累積
     */
    public function weekly(User $user): array;

    /**
     * 月間実績を取得する（月初〜月末）
     * 
     * @param User $user 対象ユーザー
     * @return array 日別の実績データ（weekly() と同じ構造）
     */
    public function monthly(User $user): array;

    /**
     * 年間実績を取得する（年初〜年末、週別集計）
     * 
     * @param User $user 対象ユーザー
     * @return array 週別の実績データ
     *               - labels: array 週ラベル（例: '1/1–1/7'）
     *               - その他は weekly() と同じ
     */
    public function yearly(User $user): array;

    /**
     * グループ全体の週間実績を取得する
     * 
     * @param Collection<int, User> $users 対象ユーザーのコレクション
     * @return array 日別の実績データ
     */
    public function weeklyForGroup(Collection $users): array;

    /**
     * グループ全体の月間実績を取得する
     * 
     * @param Collection<int, User> $users 対象ユーザーのコレクション
     * @return array 日別の実績データ
     */
    public function monthlyForGroup(Collection $users): array;

    /**
     * グループ全体の年間実績を取得する
     * 
     * @param Collection<int, User> $users 対象ユーザーのコレクション
     * @return array 週別の実績データ
     */
    public function yearlyForGroup(Collection $users): array;
}