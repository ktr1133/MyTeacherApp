<?php

namespace App\Http\Actions\Admin\Portal;

use App\Http\Requests\Admin\Portal\UpdateMaintenanceRequest;
use App\Models\Maintenance;
use App\Services\Portal\MaintenanceServiceInterface;
use Illuminate\Http\RedirectResponse;

/**
 * メンテナンス情報更新アクション
 */
class UpdateMaintenanceAction
{
    public function __construct(
        private MaintenanceServiceInterface $maintenanceService
    ) {}

    /**
     * メンテナンス情報を更新
     *
     * @param UpdateMaintenanceRequest $request
     * @param Maintenance $maintenance
     * @return RedirectResponse
     */
    public function __invoke(UpdateMaintenanceRequest $request, Maintenance $maintenance): RedirectResponse
    {
        try {
            $this->maintenanceService->update($maintenance, $request->validated());

            return redirect()
                ->route('admin.portal.maintenances.index')
                ->with('success', 'メンテナンス情報を更新しました。');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'メンテナンス情報の更新に失敗しました。'])
                ->withInput();
        }
    }
}
