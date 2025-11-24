<?php

namespace App\Http\Actions\Portal;

use App\Services\Portal\AppUpdateServiceInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 更新履歴表示アクション
 */
class ShowUpdatesAction
{
    public function __construct(
        private AppUpdateServiceInterface $updateService
    ) {}

    /**
     * 更新履歴を表示
     *
     * @param Request $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        $appName = $request->query('app');
        $majorOnly = $request->query('major_only', false);
        
        $updates = $this->updateService->getUpdates($appName, (bool)$majorOnly);
        
        return view('portal.updates', [
            'updates' => $updates,
            'appName' => $appName,
            'majorOnly' => $majorOnly,
        ]);
    }
}
