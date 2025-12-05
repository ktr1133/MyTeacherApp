<?php

namespace App\Http\Actions\Api\Report;

use App\Services\Report\PerformanceServiceInterface;
use App\Services\Profile\ProfileManagementServiceInterface;
use App\Services\Subscription\SubscriptionServiceInterface;
use App\Http\Responders\Api\Report\ReportApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * パフォーマンス実績一覧取得API
 * 
 * GET /api/v1/reports/performance
 * 
 * 通常タスク・グループタスクの実績データ（週間・月間・年間）を取得
 */
class IndexPerformanceApiAction
{
    /**
     * コンストラクタ
     * 
     * @param PerformanceServiceInterface $performanceService パフォーマンスサービス
     * @param ProfileManagementServiceInterface $profileService プロフィール管理サービス
     * @param SubscriptionServiceInterface $subscriptionService サブスクリプションサービス
     * @param ReportApiResponder $responder レスポンダー
     */
    public function __construct(
        protected PerformanceServiceInterface $performanceService,
        protected ProfileManagementServiceInterface $profileService,
        protected SubscriptionServiceInterface $subscriptionService,
        protected ReportApiResponder $responder
    ) {}

    /**
     * パフォーマンス実績データ取得
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        $group = $currentUser->group;
        
        // テーマ判定
        $isChildTheme = $currentUser->useChildTheme();

        // パラメータ取得（テーマ別初期値）
        $tab = $request->input('tab', $isChildTheme ? 'group' : 'normal'); // normal | group
        $period = $request->input('period', $isChildTheme ? 'month' : 'week'); // week | month | year
        $offset = (int) $request->input('offset', 0);
        $selectedUserId = (int) $request->input('user_id', 0);

        // サブスクリプション制限チェック
        $hasSubscription = $group ? $this->subscriptionService->isGroupSubscribed($group) : false;
        $restrictions = $this->checkRestrictions($group, $period, $offset, $selectedUserId);

        // 制限適用後の実際の値
        $actualPeriod = $restrictions['period'];
        $actualOffset = $restrictions['offset'];
        $actualUserId = $restrictions['user_id'];

        // 通常タスクのデータ取得（常に本人）
        $normalData = $this->getPerformanceData($currentUser, $actualPeriod, $actualOffset, false);

        // グループタスクのデータ取得
        $groupData = null;
        $members = collect();
        $targetUser = null;
        $isGroupWhole = false;

        if ($currentUser->canEditGroup() && $currentUser->group_id) {
            $members = $this->profileService->getGroupMembers($currentUser->group_id);

            if ($actualUserId == 0) {
                // グループ全体
                $isGroupWhole = true;
                $groupData = $this->getPerformanceData($members, $actualPeriod, $actualOffset, true);
            } else {
                // 特定メンバー
                $targetUser = $this->profileService->findUserById($actualUserId);
                
                if ($targetUser && $targetUser->group_id === $currentUser->group_id) {
                    $groupData = $this->getPerformanceData($targetUser, $actualPeriod, $actualOffset, true);
                } else {
                    $actualUserId = 0;
                    $isGroupWhole = true;
                    $groupData = $this->getPerformanceData($members, $actualPeriod, $actualOffset, true);
                }
            }
        }

        return $this->responder->performance([
            'tab' => $tab,
            'period' => $actualPeriod,
            'offset' => $actualOffset,
            'selected_user_id' => $actualUserId,
            'is_child_theme' => $isChildTheme,
            'has_subscription' => $hasSubscription,
            'restrictions' => $restrictions['alerts'],
            'normal_data' => $normalData,
            'group_data' => $groupData,
            'members' => $members,
            'target_user' => $targetUser,
            'is_group_whole' => $isGroupWhole,
        ]);
    }

    /**
     * サブスクリプション制限チェック
     * 
     * @param \App\Models\Group|null $group
     * @param string $period
     * @param int $offset
     * @param int $userId
     * @return array
     */
    protected function checkRestrictions($group, string $period, int $offset, int $userId): array
    {
        $alerts = [];
        $actualPeriod = $period;
        $actualOffset = $offset;
        $actualUserId = $userId;

        if (!$group) {
            return [
                'period' => $actualPeriod,
                'offset' => $actualOffset,
                'user_id' => $actualUserId,
                'alerts' => $alerts,
            ];
        }

        // 期間選択制限（無料は週間のみ）
        if (!$this->subscriptionService->canSelectPeriod($group, $period)) {
            $actualPeriod = 'week';
            $alerts[] = ['type' => 'period', 'message' => '無料プランでは週間表示のみ利用可能です。'];
        }

        // メンバー選択制限（無料は個人選択不可）
        if ($userId > 0 && !$this->subscriptionService->canSelectMember($group, true)) {
            $actualUserId = 0;
            $alerts[] = ['type' => 'member', 'message' => '無料プランではメンバー個別選択はできません。'];
        }

        // 期間ナビゲーション制限（無料は当週のみ）
        if ($offset !== 0) {
            $targetPeriod = $this->calculateTargetPeriod($actualPeriod, $offset);
            if (!$this->subscriptionService->canNavigateToPeriod($group, $targetPeriod)) {
                $actualOffset = 0;
                $alerts[] = ['type' => 'navigation', 'message' => '無料プランでは過去のデータ閲覧に制限があります。'];
            }
        }

        return [
            'period' => $actualPeriod,
            'offset' => $actualOffset,
            'user_id' => $actualUserId,
            'alerts' => $alerts,
        ];
    }

    /**
     * パフォーマンスデータ取得
     * 
     * @param mixed $target User|Collection
     * @param string $period
     * @param int $offset
     * @param bool $isGroup
     * @return array|null
     */
    protected function getPerformanceData($target, string $period, int $offset, bool $isGroup): ?array
    {
        if (!$target) {
            return null;
        }

        if ($isGroup && $target instanceof \Illuminate\Database\Eloquent\Collection) {
            // グループ全体
            return match($period) {
                'week' => $this->performanceService->weeklyForGroupWithOffset($target, $offset),
                'month' => $this->performanceService->monthlyForGroupWithOffset($target, $offset),
                'year' => $this->performanceService->yearlyForGroupWithOffset($target, $offset),
                default => null,
            };
        } else {
            // 個人
            return match($period) {
                'week' => $this->performanceService->weeklyWithOffset($target, $offset),
                'month' => $this->performanceService->monthlyWithOffset($target, $offset),
                'year' => $this->performanceService->yearlyWithOffset($target, $offset),
                default => null,
            };
        }
    }

    /**
     * 対象期間の計算
     * 
     * @param string $period
     * @param int $offset
     * @return \DateTime
     */
    protected function calculateTargetPeriod(string $period, int $offset): \DateTime
    {
        $date = new \DateTime();
        
        switch($period) {
            case 'week':
                $date->modify("{$offset} weeks");
                break;
            case 'month':
                $date->modify("{$offset} months");
                break;
            case 'year':
                $date->modify("{$offset} years");
                break;
        }
        
        return $date;
    }
}
