<?php

namespace App\Http\Actions\Admin\Portal;

use App\Models\Maintenance;
use App\Services\Portal\MaintenanceServiceInterface;
use Illuminate\Http\RedirectResponse;

/**
 * メンテナンス情報削除アクション
 */
class DeleteMaintenanceAction
{
    public function __construct(
        private MaintenanceServiceInterface $maintenanceService
    ) {}

    /**
     * メンテナンス情報を削除
     *
     * @param Maintenance $maintenance
     * @return RedirectResponse
     */
    public function __invoke(Maintenance $maintenance): RedirectResponse
    {
        try {
            $this->maintenanceService->delete($maintenance);

            return redirect()
                ->route('admin.portal.maintenances.index')
                ->with('success', 'メンテナンス情報を削除しました。');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'メンテナンス情報の削除に失敗しました。']);
        }
    }
}
