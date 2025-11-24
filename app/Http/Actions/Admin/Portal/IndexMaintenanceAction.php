<?php

namespace App\Http\Actions\Admin\Portal;

use App\Services\Portal\MaintenanceServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * メンテナンス情報一覧表示アクション
 */
class IndexMaintenanceAction
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
        $filters = [
            'status' => $request->input('status'),
            'app_name' => $request->input('app_name'),
        ];

        $maintenances = $this->maintenanceService->paginate($filters, 15);

        return view('admin.portal.maintenances.index', [
            'maintenances' => $maintenances,
            'filters' => $filters,
        ]);
    }
}
