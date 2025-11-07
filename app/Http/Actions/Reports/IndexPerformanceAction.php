<?php

namespace App\Http\Actions\Reports;

use App\Services\Report\PerformanceServiceInterface;
use App\Services\Profile\ProfileManagementServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IndexPerformanceAction
{
    public function __construct(
        private PerformanceServiceInterface $performanceService,
        private ProfileManagementServiceInterface $profileService
    ) {}

    public function __invoke(Request $request)
    {
        $currentUser = Auth::user();

        // ========================================
        // URLパラメータの取得と検証
        // ========================================
        $tab = $request->input('tab', 'normal'); // normal | group
        $period = $request->input('period', 'week'); // week | month | year
        $offset = (int) $request->input('offset', 0);
        $selectedUserId = (int) $request->input('user_id', 0);

        // オフセットの妥当性チェック
        $offset = $this->validateOffset($period, $offset);

        // ========================================
        // 通常タスクのデータ取得（常に本人）
        // ========================================
        $normalData = $this->getPerformanceData($currentUser, $period, $offset, false);

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
                $groupData = $this->getPerformanceData($members, $period, $offset, true);
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
                    $groupData = $this->getPerformanceData($members, $period, $offset, true);
                } else {
                    // 有効な個人選択
                    $groupData = $this->getPerformanceData($targetUser, $period, $offset, false);
                }
            }
        } else {
            // 編集権限がない場合 → 自分のグループタスクのみ
            $targetUser = $currentUser;
            $groupData = $this->getPerformanceData($currentUser, $period, $offset, false);
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
            'isGroupWhole'
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
}