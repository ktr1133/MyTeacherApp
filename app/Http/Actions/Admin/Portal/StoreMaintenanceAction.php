<?php

namespace App\Http\Actions\Admin\Portal;

use App\Http\Requests\Admin\Portal\StoreMaintenanceRequest;
use App\Services\Portal\MaintenanceServiceInterface;
use Illuminate\Http\RedirectResponse;

/**
 * メンテナンス情報登録アクション
 */
class StoreMaintenanceAction
{
    public function __construct(
        private MaintenanceServiceInterface $maintenanceService
    ) {}

    /**
     * メンテナンス情報を登録
     *
     * @param StoreMaintenanceRequest $request
     * @return RedirectResponse
     */
    public function __invoke(StoreMaintenanceRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $data['created_by'] = $request->user()->id;

            $this->maintenanceService->create($data);

            return redirect()
                ->route('admin.portal.maintenances.index')
                ->with('success', 'メンテナンス情報を登録しました。');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'メンテナンス情報の登録に失敗しました。'])
                ->withInput();
        }
    }
}
