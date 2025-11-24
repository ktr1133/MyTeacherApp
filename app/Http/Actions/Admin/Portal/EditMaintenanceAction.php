<?php

namespace App\Http\Actions\Admin\Portal;

use App\Models\Maintenance;
use App\Services\Portal\MaintenanceServiceInterface;
use Illuminate\Contracts\View\View;

/**
 * メンテナンス情報編集画面表示アクション
 */
class EditMaintenanceAction
{
    public function __construct(
        private MaintenanceServiceInterface $maintenanceService
    ) {}

    /**
     * メンテナンス情報編集画面を表示
     *
     * @param Maintenance $maintenance
     * @return View
     */
    public function __invoke(Maintenance $maintenance): View
    {
        return view('admin.portal.maintenances.edit', [
            'maintenance' => $maintenance,
        ]);
    }
}
