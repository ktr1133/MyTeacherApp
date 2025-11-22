<?php

namespace App\Http\Actions\Portal;

use App\Services\Portal\MaintenanceServiceInterface;
use App\Services\Portal\AppUpdateServiceInterface;
use Illuminate\View\View;

/**
 * ポータルトップページ表示アクション
 */
class ShowPortalHomeAction
{
    public function __construct(
        private MaintenanceServiceInterface $maintenanceService,
        private AppUpdateServiceInterface $updateService
    ) {}

    /**
     * ポータルトップページを表示
     *
     * @return View
     */
    public function __invoke(): View
    {
        // 直近3件のメンテナンス情報
        $upcomingMaintenances = $this->maintenanceService->getUpcoming(3);
        
        // 直近5件の更新履歴
        $recentUpdates = $this->updateService->getUpdates(limit: 5);
        
        return view('portal.index', [
            'maintenances' => $upcomingMaintenances,
            'updates' => $recentUpdates,
        ]);
    }
}
