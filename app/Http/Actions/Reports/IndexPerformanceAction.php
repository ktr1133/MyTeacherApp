<?php

namespace App\Http\Actions\Reports;

use App\Services\Report\PerformanceServiceInterface;
use App\Services\Profile\ProfileManagementServiceInterface;
use App\Services\Subscription\SubscriptionServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IndexPerformanceAction
{
    public function __construct(
        private PerformanceServiceInterface $performanceService,
        private ProfileManagementServiceInterface $profileService,
        private SubscriptionServiceInterface $subscriptionService
    ) {}

    public function __invoke(Request $request)
    {
        $currentUser = Auth::user();
        $group = $currentUser->group;
        
        // テーマ判定
        $isChildTheme = $currentUser->useChildTheme();

        // ========================================
        // URLパラメータの取得と検証（テーマ別初期値）
        // ========================================
        // 子ども向け: クエスト・月間、大人向け: やること・週間
        $tab = $request->input('tab', $isChildTheme ? 'group' : 'normal'); // normal | group
        $period = $request->input('period', $isChildTheme ? 'month' : 'week'); // week | month | year
        $requestedOffset = (int) $request->input('offset', 0); // ユーザーがリクエストした値（UI表示用）
        $selectedUserId = (int) $request->input('user_id', 0);

        // ========================================
        // サブスクリプション制限チェック
        // ========================================
        $hasSubscription = $group ? $this->subscriptionService->isGroupSubscribed($group) : false;
        $showSubscriptionAlert = false;
        $subscriptionAlertFeature = '';

        // 期間選択制限（無料は週間のみ）
        if ($group && !$this->subscriptionService->canSelectPeriod($group, $period)) {
            $period = 'week';
            $showSubscriptionAlert = true;
            $subscriptionAlertFeature = 'period';
        }

        // メンバー選択制限（無料は個人選択不可）
        if ($group && $selectedUserId > 0 && !$this->subscriptionService->canSelectMember($group, true)) {
            $selectedUserId = 0; // グループ全体に強制変更
            $showSubscriptionAlert = true;
            $subscriptionAlertFeature = 'member';
        }

        // オフセットの妥当性チェック
        $offset = $this->validateOffset($period, $requestedOffset);

        // 期間ナビゲーション制限（無料は当週のみ）
        // データ取得用のオフセット（actualOffset）を決定
        $actualOffset = $offset;
        if ($group && $offset !== 0) {
            $targetPeriod = $this->calculateTargetPeriod($period, $offset);
            if (!$this->subscriptionService->canNavigateToPeriod($group, $targetPeriod)) {
                $actualOffset = 0; // データは当週を取得
                $showSubscriptionAlert = true;
                $subscriptionAlertFeature = 'navigation';
            }
        }

        // ========================================
        // 通常タスクのデータ取得（常に本人）
        // ========================================
        $normalData = $this->getPerformanceData($currentUser, $period, $actualOffset, false);

        // ========================================
        // グループタスク用の初期化
        // ========================================
        $members = collect();
        $targetUser = null;
        $groupData = null;
        $isGroupWhole = false;

        // ========================================
        // グループタスクのデータ取得
        // ========================================
        if ($currentUser->canEditGroup() && $currentUser->group_id) {
            // グループ編集権限がある場合 → メンバー選択可能
            $members = $this->profileService->getGroupMembers($currentUser->group_id);

            if ($selectedUserId == 0) {
                // グループ全体を選択
                $isGroupWhole = true;
                $groupData = $this->getPerformanceData($members, $period, $actualOffset, true);
            } else {
                // 特定メンバーを選択
                $targetUser = $this->profileService->findUserById($selectedUserId);

                // 選択されたユーザーの検証
                if (!$targetUser 
                    || $targetUser->group_id !== $currentUser->group_id 
                    || $targetUser->canEditGroup()) {
                    // 無効な選択の場合は全体に戻す
                    $isGroupWhole = true;
                    $targetUser = null;
                    $groupData = $this->getPerformanceData($members, $period, $actualOffset, true);
                } else {
                    // 有効な個人選択
                    $groupData = $this->getPerformanceData($targetUser, $period, $actualOffset, false);
                }
            }
        } else {
            // 編集権限がない場合 → 自分のグループタスクのみ
            $targetUser = $currentUser;
            $groupData = $this->getPerformanceData($currentUser, $period, $actualOffset, false);
        }

        // ========================================
        // ビューへデータを渡す
        // ========================================
        return view('reports.performance', compact(
            'tab',
            'period',
            'offset',
            'normalData',
            'groupData',
            'members',
            'targetUser',
            'isGroupWhole',
            'hasSubscription',
            'showSubscriptionAlert',
            'subscriptionAlertFeature'
        ));
    }

    /**
     * 期間とオフセットに応じたパフォーマンスデータを取得
     *
     * @param mixed $target User または Collection
     * @param string $period week | month | year
     * @param int $offset
     * @param bool $isGroup グループデータかどうか
     * @return array
     */
    private function getPerformanceData($target, string $period, int $offset, bool $isGroup): array
    {
        if ($isGroup) {
            // グループデータの取得
            return match($period) {
                'week' => $this->performanceService->weeklyForGroupWithOffset($target, $offset),
                'month' => $this->performanceService->monthlyForGroupWithOffset($target, $offset),
                'year' => $this->performanceService->yearlyForGroupWithOffset($target, $offset),
                default => $this->performanceService->weeklyForGroupWithOffset($target, $offset),
            };
        } else {
            // 個人データの取得
            return match($period) {
                'week' => $this->performanceService->weeklyWithOffset($target, $offset),
                'month' => $this->performanceService->monthlyWithOffset($target, $offset),
                'year' => $this->performanceService->yearlyWithOffset($target, $offset),
                default => $this->performanceService->weeklyWithOffset($target, $offset),
            };
        }
    }

    /**
     * オフセットの妥当性をチェック
     *
     * @param string $period
     * @param int $offset
     * @return int
     */
    private function validateOffset(string $period, int $offset): int
    {
        $maxOffset = match($period) {
            'week' => -52,
            'month' => -12,
            'year' => -5,
            default => -52,
        };

        // -52 ～ 0 の範囲に制限
        return max($maxOffset, min(0, $offset));
    }

    /**
     * オフセットから対象期間の日付を計算
     *
     * @param string $period
     * @param int $offset
     * @return \Carbon\Carbon
     */
    private function calculateTargetPeriod(string $period, int $offset): \Carbon\Carbon
    {
        $now = now();

        return match($period) {
            'week' => $now->addWeeks($offset)->startOfWeek(),
            'month' => $now->addMonths($offset)->startOfMonth(),
            'year' => $now->addYears($offset)->startOfYear(),
            default => $now->addWeeks($offset)->startOfWeek(),
        };
    }
}