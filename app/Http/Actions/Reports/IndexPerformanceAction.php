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
        
        // 通常タスクは常に本人
        $normalUser = $currentUser;
        $weekNormal  = $this->performanceService->weekly($normalUser);
        $monthNormal = $this->performanceService->monthly($normalUser);
        $yearNormal  = $this->performanceService->yearly($normalUser);

        // グループタスク用のデータ
        $members = collect();
        $targetUser = null;
        $weekGroup = $monthGroup = $yearGroup = null;
        $isGroupWhole = false;

        // グループ編集権限がある場合はメンバー選択可能
        if ($currentUser->canEditGroup() && $currentUser->group_id) {
            // グループメンバー（編集権限なし）を取得
            $members = $this->profileService->getGroupMembers($currentUser->group_id);
            
            // 選択されたユーザーIDを取得（デフォルトは「全体」= 0）
            $selectedUserId = $request->input('user_id', 0);

            if ($selectedUserId == 0) {
                // グループ全体
                $isGroupWhole = true;
                $weekGroup  = $this->performanceService->weeklyForGroup($members);
                $monthGroup = $this->performanceService->monthlyForGroup($members);
                $yearGroup  = $this->performanceService->yearlyForGroup($members);
            } else {
                // 特定メンバー
                $targetUser = $this->profileService->findUserById($selectedUserId);
                
                // 選択されたユーザーが同一グループでない場合は全体に戻す
                if (!$targetUser || $targetUser->group_id !== $currentUser->group_id || $targetUser->canEditGroup()) {
                    $isGroupWhole = true;
                    $weekGroup  = $this->performanceService->weeklyForGroup($members);
                    $monthGroup = $this->performanceService->monthlyForGroup($members);
                    $yearGroup  = $this->performanceService->yearlyForGroup($members);
                } else {
                    $weekGroup  = $this->performanceService->weekly($targetUser);
                    $monthGroup = $this->performanceService->monthly($targetUser);
                    $yearGroup  = $this->performanceService->yearly($targetUser);
                }
            }
        } else {
            // 編集権限がない場合は自分のグループタスクのみ
            $targetUser = $currentUser;
            $weekGroup  = $this->performanceService->weekly($currentUser);
            $monthGroup = $this->performanceService->monthly($currentUser);
            $yearGroup  = $this->performanceService->yearly($currentUser);
        }

        return view('reports.performance', compact(
            'weekNormal', 'monthNormal', 'yearNormal',
            'weekGroup', 'monthGroup', 'yearGroup',
            'members', 'targetUser', 'isGroupWhole', 'normalUser'
        ));
    }
}