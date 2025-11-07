<?php

namespace App\Services\Report;

use App\Models\User;
use App\Repositories\Report\ReportRepositoryInterface;
use App\Services\Report\PerformanceServiceInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * 実績レポート集計サービスの実装クラス
 * 
 * レポジトリから取得したデータを日別/週別に整形し、グラフ表示用の配列を返します。
 */
class PerformanceService implements PerformanceServiceInterface
{
    public function __construct(
        private ReportRepositoryInterface $reportRepository
    ) {}

    // ========================================
    // 通常タスク(オフセット対応)
    // ========================================

    /**
     * 指定された週オフセットに基づいて、ユーザーの週次パフォーマンスデータを取得します。
     *
     * @param User $user 対象ユーザー
     * @param int $weekOffset 週オフセット(0が今週、-1が先週、1が来週)
     * @return array グラフ表示用のデータ配列
     */
    public function weeklyWithOffset(User $user, int $weekOffset): array
    {
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeeks($weekOffset);
        $endOfWeek = $startOfWeek->copy()->endOfWeek(Carbon::SUNDAY);

        // 既存のメソッドを使用してデータ取得
        $data = $this->aggregateByDays($user, $startOfWeek, $endOfWeek);

        // 期間情報を追加
        $data['periodInfo'] = [
            'start' => $startOfWeek->format('Y-m-d'),
            'end' => $endOfWeek->format('Y-m-d'),
            'displayText' => $this->getWeekDisplayText($startOfWeek),
            'canGoPrevious' => $weekOffset > -52,
            'canGoNext' => $weekOffset < 0,
        ];

        return $data;
    }

    /**
     * 指定された月オフセットに基づいて、ユーザーの月次パフォーマンスデータを取得します。
     *
     * @param User $user 対象ユーザー
     * @param int $monthOffset 月オフセット(0が今月、-1が先月、1が来月)
     * @return array グラフ表示用のデータ配列
     */
    public function monthlyWithOffset(User $user, int $monthOffset): array
    {
        $startOfMonth = Carbon::now()->startOfMonth()->addMonths($monthOffset);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // 既存のメソッドを使用してデータ取得
        $data = $this->aggregateByDays($user, $startOfMonth, $endOfMonth);

        // 期間情報を追加
        $data['periodInfo'] = [
            'start' => $startOfMonth->format('Y-m-d'),
            'end' => $endOfMonth->format('Y-m-d'),
            'displayText' => $startOfMonth->format('Y年n月'),
            'canGoPrevious' => $monthOffset > -12,
            'canGoNext' => $monthOffset < 0,
        ];

        return $data;
    }

    /**
     * 指定された年オフセットに基づいて、ユーザーの年次パフォーマンスデータを取得します。
     *
     * @param User $user 対象ユーザー
     * @param int $yearOffset 年オフセット(0が今年、-1が昨年、1が来年)
     * @return array グラフ表示用のデータ配列
     */
    public function yearlyWithOffset(User $user, int $yearOffset): array
    {
        $startOfYear = Carbon::now()->startOfYear()->addYears($yearOffset);
        $endOfYear = $startOfYear->copy()->endOfYear();

        // 既存のメソッドを使用してデータ取得
        $data = $this->aggregateByWeeks($user, $startOfYear, $endOfYear);

        // 期間情報を追加
        $data['periodInfo'] = [
            'start' => $startOfYear->format('Y-m-d'),
            'end' => $endOfYear->format('Y-m-d'),
            'displayText' => $startOfYear->format('Y年'),
            'canGoPrevious' => $yearOffset > -5,
            'canGoNext' => $yearOffset < 0,
        ];

        return $data;
    }

    // ========================================
    // グループタスク(オフセット対応)
    // ========================================

    /**
     * 指定された週オフセットに基づいて、グループの週次パフォーマンスデータを取得します。
     *
     * @param Collection $users 対象ユーザーコレクション
     * @param int $weekOffset 週オフセット(0が今週、-1が先週、1が来週)
     * @return array グラフ表示用のデータ配列
     */
    public function weeklyForGroupWithOffset(Collection $users, int $weekOffset): array
    {
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeeks($weekOffset);
        $endOfWeek = $startOfWeek->copy()->endOfWeek(Carbon::SUNDAY);

        // 既存のメソッドを使用してデータ取得
        $data = $this->aggregateByDaysForGroup($users, $startOfWeek, $endOfWeek);

        // 期間情報を追加
        $data['periodInfo'] = [
            'start' => $startOfWeek->format('Y-m-d'),
            'end' => $endOfWeek->format('Y-m-d'),
            'displayText' => $this->getWeekDisplayText($startOfWeek),
            'canGoPrevious' => $weekOffset > -52,
            'canGoNext' => $weekOffset < 0,
        ];

        return $data;
    }

    /**
     * 指定された月オフセットに基づいて、グループの月次パフォーマンスデータを取得します。
     *
     * @param Collection $users 対象ユーザーコレクション
     * @param int $monthOffset 月オフセット(0が今月、-1が先月、1が来月)
     * @return array グラフ表示用のデータ配列
     */
    public function monthlyForGroupWithOffset(Collection $users, int $monthOffset): array
    {
        $startOfMonth = Carbon::now()->startOfMonth()->addMonths($monthOffset);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // 既存のメソッドを使用してデータ取得
        $data = $this->aggregateByDaysForGroup($users, $startOfMonth, $endOfMonth);

        // 期間情報を追加
        $data['periodInfo'] = [
            'start' => $startOfMonth->format('Y-m-d'),
            'end' => $endOfMonth->format('Y-m-d'),
            'displayText' => $startOfMonth->format('Y年n月'),
            'canGoPrevious' => $monthOffset > -12,
            'canGoNext' => $monthOffset < 0,
        ];

        return $data;
    }

    /**
     * 指定された年オフセットに基づいて、グループの年次パフォーマンスデータを取得します。
     *
     * @param Collection $users 対象ユーザーコレクション
     * @param int $yearOffset 年オフセット(0が今年、-1が昨年、1が来年)
     * @return array グラフ表示用のデータ配列
     */
    public function yearlyForGroupWithOffset(Collection $users, int $yearOffset): array
    {
        $startOfYear = Carbon::now()->startOfYear()->addYears($yearOffset);
        $endOfYear = $startOfYear->copy()->endOfYear();

        // 既存のメソッドを使用してデータ取得
        $data = $this->aggregateByWeeksForGroup($users, $startOfYear, $endOfYear);

        // 期間情報を追加
        $data['periodInfo'] = [
            'start' => $startOfYear->format('Y-m-d'),
            'end' => $endOfYear->format('Y-m-d'),
            'displayText' => $startOfYear->format('Y年'),
            'canGoPrevious' => $yearOffset > -5,
            'canGoNext' => $yearOffset < 0,
        ];

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function weekly(User $user): array
    {
        $start = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $end   = (clone $start)->endOfWeek(Carbon::SUNDAY);

        return $this->aggregateByDays($user, $start, $end);
    }

    /**
     * {@inheritdoc}
     */
    public function monthly(User $user): array
    {
        $start = Carbon::now()->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        return $this->aggregateByDays($user, $start, $end);
    }

    /**
     * {@inheritdoc}
     */
    public function yearly(User $user): array
    {
        $start = Carbon::now()->startOfYear();
        $end   = (clone $start)->endOfYear();

        return $this->aggregateByWeeks($user, $start, $end);
    }

    /**
     * {@inheritdoc}
     */
    public function weeklyForGroup(Collection $users): array
    {
        $start = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $end   = (clone $start)->endOfWeek(Carbon::SUNDAY);

        return $this->aggregateByDaysForGroup($users, $start, $end);
    }

    /**
     * {@inheritdoc}
     */
    public function monthlyForGroup(Collection $users): array
    {
        $start = Carbon::now()->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        return $this->aggregateByDaysForGroup($users, $start, $end);
    }

    /**
     * {@inheritdoc}
     */
    public function yearlyForGroup(Collection $users): array
    {
        $start = Carbon::now()->startOfYear();
        $end   = (clone $start)->endOfYear();

        return $this->aggregateByWeeksForGroup($users, $start, $end);
    }

    /**
     * 日単位集計（通常/グループ、完了/未完、累積、グループ報酬累積）
     */
    private function aggregateByDays(User $user, Carbon $start, Carbon $end): array
    {
        $nCompletedMap = $this->reportRepository->getNormalCompletedCountsByDate($user->id, $start, $end);
        $nIncompleteMap = $this->reportRepository->getNormalIncompleteCountsByDueDate($user->id, $start, $end);
        $gCompletedMap = $this->reportRepository->getGroupCompletedCountsByDate($user->id, $start, $end);
        $gIncompleteMap = $this->reportRepository->getGroupIncompleteCountsByDueDate($user->id, $start, $end);
        $gRewardMap = $this->reportRepository->getGroupRewardByDate($user->id, $start, $end);

        return $this->buildDailyData($start, $end, $nCompletedMap, $nIncompleteMap, $gCompletedMap, $gIncompleteMap, $gRewardMap);
    }

    /**
     * グループ全体の日単位集計
     */
    private function aggregateByDaysForGroup(Collection $users, Carbon $start, Carbon $end): array
    {
        $nCompletedMap = [];
        $nIncompleteMap = [];
        $gCompletedMap = [];
        $gIncompleteMap = [];
        $gRewardMap = [];

        foreach ($users as $user) {
            $nCompletedMap = $this->mergeCountMaps($nCompletedMap, $this->reportRepository->getNormalCompletedCountsByDate($user->id, $start, $end));
            $nIncompleteMap = $this->mergeCountMaps($nIncompleteMap, $this->reportRepository->getNormalIncompleteCountsByDueDate($user->id, $start, $end));
            $gCompletedMap = $this->mergeCountMaps($gCompletedMap, $this->reportRepository->getGroupCompletedCountsByDate($user->id, $start, $end));
            $gIncompleteMap = $this->mergeCountMaps($gIncompleteMap, $this->reportRepository->getGroupIncompleteCountsByDueDate($user->id, $start, $end));
            $gRewardMap = $this->mergeCountMaps($gRewardMap, $this->reportRepository->getGroupRewardByDate($user->id, $start, $end));
        }

        return $this->buildDailyData($start, $end, $nCompletedMap, $nIncompleteMap, $gCompletedMap, $gIncompleteMap, $gRewardMap);
    }

    /**
     * 週単位集計（ISO週: 月曜始まり）
     */
    private function aggregateByWeeks(User $user, Carbon $start, Carbon $end): array
    {
        $labels = [];
        $nDone = $nTodo = $gDone = $gTodo = $gReward = [];
        $nCum = $gCum = $gRewardCum = [];
        $nAcc = $gAcc = $rAcc = 0;

        $wStart = (clone $start)->startOfWeek(Carbon::MONDAY);
        while ($wStart->lte($end)) {
            $wEnd = (clone $wStart)->endOfWeek(Carbon::SUNDAY);
            if ($wEnd->gt($end)) $wEnd = (clone $end);
            $labels[] = $wStart->format('n/j') . '–' . $wEnd->format('n/j');

            $nCompletedMap = $this->reportRepository->getNormalCompletedCountsByDate($user->id, $wStart, $wEnd);
            $nIncompleteMap = $this->reportRepository->getNormalIncompleteCountsByDueDate($user->id, $wStart, $wEnd);
            $gCompletedMap = $this->reportRepository->getGroupCompletedCountsByDate($user->id, $wStart, $wEnd);
            $gIncompleteMap = $this->reportRepository->getGroupIncompleteCountsByDueDate($user->id, $wStart, $wEnd);
            $gRewardMap = $this->reportRepository->getGroupRewardByDate($user->id, $wStart, $wEnd);

            $normalCompleted = array_sum($nCompletedMap);
            $normalIncomplete = array_sum($nIncompleteMap);
            $groupCompleted = array_sum($gCompletedMap);
            $groupIncomplete = array_sum($gIncompleteMap);
            $groupReward = (int) array_sum($gRewardMap);

            $nDone[] = $normalCompleted;
            $nTodo[] = $normalIncomplete;
            $gDone[] = $groupCompleted;
            $gTodo[] = $groupIncomplete;
            $gReward[] = $groupReward;

            $nAcc += $normalCompleted;
            $gAcc += $groupCompleted;
            $rAcc += $groupReward;

            $nCum[] = $nAcc;
            $gCum[] = $gAcc;
            $gRewardCum[] = $rAcc;

            $wStart->addWeek();
        }

        return compact('labels', 'nDone', 'nTodo', 'nCum', 'gDone', 'gTodo', 'gCum', 'gReward', 'gRewardCum');
    }

    /**
     * グループ全体の週単位集計
     */
    private function aggregateByWeeksForGroup(Collection $users, Carbon $start, Carbon $end): array
    {
        $labels = [];
        $nDone = $nTodo = $gDone = $gTodo = $gReward = [];
        $nCum = $gCum = $gRewardCum = [];
        $nAcc = $gAcc = $rAcc = 0;

        $wStart = (clone $start)->startOfWeek(Carbon::MONDAY);
        while ($wStart->lte($end)) {
            $wEnd = (clone $wStart)->endOfWeek(Carbon::SUNDAY);
            if ($wEnd->gt($end)) $wEnd = (clone $end);
            $labels[] = $wStart->format('n/j') . '–' . $wEnd->format('n/j');

            $nCompletedMap = [];
            $nIncompleteMap = [];
            $gCompletedMap = [];
            $gIncompleteMap = [];
            $gRewardMap = [];

            foreach ($users as $user) {
                $nCompletedMap = $this->mergeCountMaps($nCompletedMap, $this->reportRepository->getNormalCompletedCountsByDate($user->id, $wStart, $wEnd));
                $nIncompleteMap = $this->mergeCountMaps($nIncompleteMap, $this->reportRepository->getNormalIncompleteCountsByDueDate($user->id, $wStart, $wEnd));
                $gCompletedMap = $this->mergeCountMaps($gCompletedMap, $this->reportRepository->getGroupCompletedCountsByDate($user->id, $wStart, $wEnd));
                $gIncompleteMap = $this->mergeCountMaps($gIncompleteMap, $this->reportRepository->getGroupIncompleteCountsByDueDate($user->id, $wStart, $wEnd));
                $gRewardMap = $this->mergeCountMaps($gRewardMap, $this->reportRepository->getGroupRewardByDate($user->id, $wStart, $wEnd));
            }

            $normalCompleted = array_sum($nCompletedMap);
            $normalIncomplete = array_sum($nIncompleteMap);
            $groupCompleted = array_sum($gCompletedMap);
            $groupIncomplete = array_sum($gIncompleteMap);
            $groupReward = (int) array_sum($gRewardMap);

            $nDone[] = $normalCompleted;
            $nTodo[] = $normalIncomplete;
            $gDone[] = $groupCompleted;
            $gTodo[] = $groupIncomplete;
            $gReward[] = $groupReward;

            $nAcc += $normalCompleted;
            $gAcc += $groupCompleted;
            $rAcc += $groupReward;

            $nCum[] = $nAcc;
            $gCum[] = $gAcc;
            $gRewardCum[] = $rAcc;

            $wStart->addWeek();
        }

        return compact('labels', 'nDone', 'nTodo', 'nCum', 'gDone', 'gTodo', 'gCum', 'gReward', 'gRewardCum');
    }

    /**
     * 日別データ構築の共通処理
     */
    private function buildDailyData(Carbon $start, Carbon $end, array $nCompletedMap, array $nIncompleteMap, array $gCompletedMap, array $gIncompleteMap, array $gRewardMap): array
    {
        $labels = [];
        $nDone = $nTodo = $gDone = $gTodo = $gReward = [];
        $nCum = $gCum = $gRewardCum = [];
        $nAcc = $gAcc = $rAcc = 0;

        for ($d = (clone $start); $d->lte($end); $d->addDay()) {
            $dateKey = $d->toDateString();
            $labels[] = $d->format('n/j');

            $normalCompleted = $nCompletedMap[$dateKey] ?? 0;
            $normalIncomplete = $nIncompleteMap[$dateKey] ?? 0;
            $groupCompleted = $gCompletedMap[$dateKey] ?? 0;
            $groupIncomplete = $gIncompleteMap[$dateKey] ?? 0;
            $groupReward = (int) ($gRewardMap[$dateKey] ?? 0);

            $nDone[] = $normalCompleted;
            $nTodo[] = $normalIncomplete;
            $gDone[] = $groupCompleted;
            $gTodo[] = $groupIncomplete;
            $gReward[] = $groupReward;

            $nAcc += $normalCompleted;
            $gAcc += $groupCompleted;
            $rAcc += $groupReward;

            $nCum[] = $nAcc;
            $gCum[] = $gAcc;
            $gRewardCum[] = $rAcc;
        }

        return compact('labels', 'nDone', 'nTodo', 'nCum', 'gDone', 'gTodo', 'gCum', 'gReward', 'gRewardCum');
    }

    /**
     * カウントマップをマージする
     */
    private function mergeCountMaps(array $map1, array $map2): array
    {
        foreach ($map2 as $key => $value) {
            $map1[$key] = ($map1[$key] ?? 0) + $value;
        }
        return $map1;
    }

    // ========================================
    // ヘルパーメソッド
    // ========================================

    private function getWeekDisplayText(Carbon $startOfWeek): string
    {
        $weekOfMonth = (int) ceil($startOfWeek->day / 7);
        return $startOfWeek->format('Y年n月') . $weekOfMonth . '週目';
    }
}