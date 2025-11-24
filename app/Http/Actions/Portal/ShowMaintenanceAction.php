<?php

namespace App\Http\Actions\Portal;

use App\Services\Portal\MaintenanceServiceInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * メンテナンス情報一覧表示アクション
 */
class ShowMaintenanceAction
{
    public function __construct(
        private MaintenanceServiceInterface $maintenanceService
    ) {}

    /**
     * メンテナンス情報一覧を表示
     *
     * @param Request $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        $status = $request->query('status');
        $appName = $request->query('app');
        
        $maintenances = $this->maintenanceService->getAll($status, $appName);
        
        return view('portal.maintenance', [
            'maintenances' => $maintenances,
            'status' => $status,
            'appName' => $appName,
        ]);
    }
}
