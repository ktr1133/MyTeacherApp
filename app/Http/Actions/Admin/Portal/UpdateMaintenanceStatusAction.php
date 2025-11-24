<?php

namespace App\Http\Actions\Admin\Portal;

use App\Http\Requests\Admin\Portal\UpdateMaintenanceStatusRequest;
use App\Models\Maintenance;
use App\Services\Portal\MaintenanceServiceInterface;
use Illuminate\Http\RedirectResponse;

/**
 * メンテナンス情報ステータス更新アクション
 */
class UpdateMaintenanceStatusAction
{
    public function __construct(
        private MaintenanceServiceInterface $maintenanceService
    ) {}

    /**
     * メンテナンスのステータスを更新
     *
     * @param UpdateMaintenanceStatusRequest $request
     * @param Maintenance $maintenance
     * @return RedirectResponse
     */
    public function __invoke(UpdateMaintenanceStatusRequest $request, Maintenance $maintenance): RedirectResponse
    {
        try {
            $status = $request->validated()['status'];

            if ($status === 'in_progress') {
                $this->maintenanceService->start($maintenance);
                $message = 'メンテナンスを開始しました。';
            } elseif ($status === 'completed') {
                $this->maintenanceService->complete($maintenance);
                $message = 'メンテナンスを完了しました。';
            } else {
                $this->maintenanceService->update($maintenance, ['status' => $status]);
                $message = 'ステータスを更新しました。';
            }

            return redirect()
                ->route('admin.portal.maintenances.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'ステータスの更新に失敗しました。']);
        }
    }
}
